<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Multi-session WebSocket proxy daemon (websockify-style).
 *
 * Browser WebSocket  →  this daemon  →  Proxmox vncwebsocket (WSS on port 8006)
 *
 * Each browser connection supplies a one-time token; the daemon reads the
 * matching session file written by VmController::terminal(), then connects
 * to Proxmox's authenticated WebSocket VNC endpoint using the admin cookie.
 * This avoids needing raw VNC TCP ports open between our server and Proxmox.
 */
class VncProxy extends Command
{
    protected $signature = 'vnc:proxy-server {--port=6080 : TCP port to listen on}';

    protected $description = 'Run the persistent VNC WebSocket proxy daemon';

    private array $sessions = [];

    public function handle(): int
    {
        set_time_limit(0);

        $port   = (int) $this->option('port');
        $server = @stream_socket_server("tcp://0.0.0.0:{$port}", $errno, $errstr);
        if (!$server) {
            $this->error("Cannot bind to port {$port}: {$errstr}");
            return 1;
        }
        stream_set_blocking($server, false);

        $this->info("VNC proxy listening on port {$port}");

        while (true) {
            $this->tick($server);
        }
    }

    // ── Main loop ─────────────────────────────────────────────────────────────

    private function tick($server): void
    {
        $read = [$server];
        foreach ($this->sessions as $sess) {
            $read[] = $sess['browser'];
            if ($sess['vnc']) {
                $read[] = $sess['vnc'];
            }
        }

        $write = $except = null;
        if (@stream_select($read, $write, $except, 1, 0) === false) {
            return;
        }

        if (in_array($server, $read, true)) {
            $client = @stream_socket_accept($server, 0);
            if ($client) {
                stream_set_blocking($client, false);
                $id = (int) $client;
                $this->sessions[$id] = [
                    'browser'     => $client,
                    'state'       => 'handshake',
                    'headerBuf'   => '',
                    'vnc'         => null,
                    'browserBuf'  => '',
                    'proxmoxBuf'  => '',
                    'deadline'    => time() + 7200,
                ];
            }
        }

        $now = time();

        foreach ($this->sessions as $id => $sess) {
            if ($now > $sess['deadline']) {
                $this->close($id);
                continue;
            }

            if ($sess['state'] === 'handshake') {
                $this->stepHandshake($id);
            } elseif ($sess['state'] === 'relay') {
                $this->stepRelay($id, $read);
            }
        }
    }

    // ── Handshake phase ───────────────────────────────────────────────────────

    private function stepHandshake(int $id): void
    {
        $browser = $this->sessions[$id]['browser'];
        $chunk   = @fread($browser, 4096);

        if ($chunk === false || ($chunk === '' && feof($browser))) {
            $this->close($id);
            return;
        }

        $this->sessions[$id]['headerBuf'] .= $chunk;
        $buf = $this->sessions[$id]['headerBuf'];

        if (!str_contains($buf, "\r\n\r\n")) {
            return;
        }

        // Parse HTTP headers from browser
        $lines   = explode("\r\n", $buf);
        $headers = ['_request_line' => $lines[0] ?? ''];
        foreach (array_slice($lines, 1) as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
        }

        // Extract one-time token from query string
        preg_match('/[?&]token=([^& ]+)/', $headers['_request_line'], $m);
        $token = isset($m[1]) ? urldecode($m[1]) : '';

        // Load session file written by VmController
        $sessionFile = sys_get_temp_dir() . "/vnc-proxy-{$token}";
        if (!$token || !file_exists($sessionFile)) {
            fwrite($browser, "HTTP/1.1 403 Forbidden\r\nContent-Length: 0\r\n\r\n");
            $this->close($id);
            return;
        }

        $data = json_decode(file_get_contents($sessionFile), true);
        @unlink($sessionFile);

        if (!$data || ($data['expires'] ?? 0) < time()) {
            fwrite($browser, "HTTP/1.1 403 Forbidden\r\nContent-Length: 0\r\n\r\n");
            $this->close($id);
            return;
        }

        // Complete WebSocket handshake with browser
        $wsKey = $headers['sec-websocket-key'] ?? '';
        if (!$wsKey) {
            $this->close($id);
            return;
        }
        $accept   = base64_encode(sha1($wsKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        $protocol = $headers['sec-websocket-protocol'] ?? '';

        $resp  = "HTTP/1.1 101 Switching Protocols\r\n";
        $resp .= "Upgrade: websocket\r\n";
        $resp .= "Connection: Upgrade\r\n";
        $resp .= "Sec-WebSocket-Accept: {$accept}\r\n";
        if ($protocol) {
            $resp .= "Sec-WebSocket-Protocol: " . trim(explode(',', $protocol)[0]) . "\r\n";
        }
        $resp .= "\r\n";
        fwrite($browser, $resp);

        // Connect to Proxmox vncwebsocket via SSL on port 8006
        $proxmoxPort = (int) ($data['proxmox_port'] ?? 8006);
        $ctx = stream_context_create(['ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ]]);
        $proxmox = @stream_socket_client(
            "ssl://{$data['vnc_host']}:{$proxmoxPort}",
            $errno, $errstr, 10,
            STREAM_CLIENT_CONNECT,
            $ctx
        );
        if (!$proxmox) {
            fwrite($browser, "\x88\x00");
            $this->close($id);
            return;
        }

        // Send WebSocket upgrade request to Proxmox (we act as WS client)
        $path = "/api2/json/nodes/{$data['node']}/qemu/{$data['vmid']}/vncwebsocket"
              . "?port={$data['vnc_port']}&vncticket=" . urlencode($data['ticket']);
        $key  = base64_encode(random_bytes(16));
        $req  = "GET {$path} HTTP/1.1\r\n"
              . "Host: {$data['vnc_host']}:{$proxmoxPort}\r\n"
              . "Cookie: PVEAuthCookie={$data['auth_cookie']}\r\n"
              . "Upgrade: websocket\r\n"
              . "Connection: Upgrade\r\n"
              . "Sec-WebSocket-Key: {$key}\r\n"
              . "Sec-WebSocket-Version: 13\r\n"
              . "Sec-WebSocket-Protocol: binary\r\n"
              . "\r\n";
        fwrite($proxmox, $req);

        // Read Proxmox 101 response (blocking, 5 s timeout)
        stream_set_timeout($proxmox, 5);
        $pxResp = '';
        while (!str_contains($pxResp, "\r\n\r\n")) {
            $chunk = fread($proxmox, 4096);
            if ($chunk === false || $chunk === '') break;
            $pxResp .= $chunk;
        }

        if (!str_contains($pxResp, '101')) {
            fwrite($browser, "\x88\x00");
            fclose($proxmox);
            $this->close($id);
            return;
        }

        stream_set_blocking($proxmox, false);

        $this->sessions[$id]['state']      = 'relay';
        $this->sessions[$id]['vnc']        = $proxmox;
        $this->sessions[$id]['proxmoxBuf'] = '';
    }

    // ── Relay phase ───────────────────────────────────────────────────────────

    private function stepRelay(int $id, array $readable): void
    {
        $browser = $this->sessions[$id]['browser'];
        $proxmox = $this->sessions[$id]['vnc'];

        // Browser → Proxmox: unmask browser WS frames, forward as masked WS client frames
        if (in_array($browser, $readable, true)) {
            $data = @fread($browser, 65536);
            if ($data === false || ($data === '' && feof($browser))) {
                $this->close($id);
                return;
            }
            if ($data !== '') {
                $this->sessions[$id]['browserBuf'] .= $data;
                $raw = $this->unwrapWsFrames($this->sessions[$id]['browserBuf']);
                if ($raw === null) {
                    $this->close($id);
                    return;
                }
                if ($raw !== '') {
                    fwrite($proxmox, $this->wrapClientFrame($raw));
                }
            }
        }

        // Proxmox → Browser: unwrap Proxmox WS frames, forward as unmasked WS server frames
        if ($proxmox && in_array($proxmox, $readable, true)) {
            $data = @fread($proxmox, 65536);
            if ($data === false || ($data === '' && feof($proxmox))) {
                $this->close($id);
                return;
            }
            if ($data !== '') {
                $this->sessions[$id]['proxmoxBuf'] .= $data;
                $raw = $this->unwrapWsFrames($this->sessions[$id]['proxmoxBuf']);
                if ($raw === null) {
                    $this->close($id);
                    return;
                }
                if ($raw !== '') {
                    fwrite($browser, $this->wrapBinaryFrame($raw));
                }
            }
        }
    }

    private function close(int $id): void
    {
        if (isset($this->sessions[$id])) {
            @fclose($this->sessions[$id]['browser']);
            if ($this->sessions[$id]['vnc']) {
                @fclose($this->sessions[$id]['vnc']);
            }
            unset($this->sessions[$id]);
        }
    }

    // ── WebSocket frame helpers ───────────────────────────────────────────────

    private function unwrapWsFrames(string &$buf): ?string
    {
        $out = '';

        while (strlen($buf) >= 2) {
            $b0     = ord($buf[0]);
            $b1     = ord($buf[1]);
            $opcode = $b0 & 0x0F;
            $masked = ($b1 & 0x80) !== 0;
            $payLen = $b1 & 0x7F;
            $offset = 2;

            if ($payLen === 126) {
                if (strlen($buf) < 4) break;
                $payLen = (ord($buf[2]) << 8) | ord($buf[3]);
                $offset = 4;
            } elseif ($payLen === 127) {
                if (strlen($buf) < 10) break;
                $payLen = 0;
                for ($i = 2; $i < 10; $i++) {
                    $payLen = ($payLen << 8) | ord($buf[$i]);
                }
                $offset = 10;
            }

            $maskLen = $masked ? 4 : 0;
            $total   = $offset + $maskLen + $payLen;
            if (strlen($buf) < $total) break;

            $maskKey = $masked ? substr($buf, $offset, 4) : '';
            $payload = substr($buf, $offset + $maskLen, $payLen);
            $buf     = substr($buf, $total);

            if ($masked) {
                for ($i = 0; $i < strlen($payload); $i++) {
                    $payload[$i] = chr(ord($payload[$i]) ^ ord($maskKey[$i % 4]));
                }
            }

            if ($opcode === 0x8) return null; // Close frame

            $out .= $payload;
        }

        return $out;
    }

    /** Unmasked binary frame (server → client direction). */
    private function wrapBinaryFrame(string $payload): string
    {
        $len   = strlen($payload);
        $frame = "\x82";
        if ($len < 126) {
            $frame .= chr($len);
        } elseif ($len < 65536) {
            $frame .= chr(126) . pack('n', $len);
        } else {
            $frame .= chr(127) . "\x00\x00\x00\x00" . pack('N', $len);
        }
        return $frame . $payload;
    }

    /** Masked binary frame (client → server direction). */
    private function wrapClientFrame(string $payload): string
    {
        $len   = strlen($payload);
        $frame = "\x82";
        if ($len < 126) {
            $frame .= chr(0x80 | $len);
        } elseif ($len < 65536) {
            $frame .= chr(0x80 | 126) . pack('n', $len);
        } else {
            $frame .= chr(0x80 | 127) . "\x00\x00\x00\x00" . pack('N', $len);
        }
        $mask   = random_bytes(4);
        $frame .= $mask;
        for ($i = 0; $i < $len; $i++) {
            $frame .= chr(ord($payload[$i]) ^ ord($mask[$i % 4]));
        }
        return $frame;
    }
}
