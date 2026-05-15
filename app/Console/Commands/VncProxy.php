<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Multi-session WebSocket-to-VNC-TCP proxy (websockify-style).
 *
 * Runs as a persistent daemon on a fixed port.  Each browser WebSocket
 * connection provides a one-time token; the proxy reads the matching
 * session file written by VmController::terminal() and connects to the
 * Proxmox raw VNC TCP port.  Raw VNC uses VNC-protocol auth (ticket =
 * VNC password), so no Proxmox HTTP cookies are required from the browser.
 */
class VncProxy extends Command
{
    protected $signature = 'vnc:proxy-server {--port=6080 : TCP port to listen on}';

    protected $description = 'Run the persistent VNC WebSocket proxy daemon';

    // Per-session state keys: browser, state, headerBuf, vnc, browserBuf, deadline
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

        // Accept new browser connections
        if (in_array($server, $read, true)) {
            $client = @stream_socket_accept($server, 0);
            if ($client) {
                stream_set_blocking($client, false);
                $id = (int) $client;
                $this->sessions[$id] = [
                    'browser'    => $client,
                    'state'      => 'handshake',
                    'headerBuf'  => '',
                    'vnc'        => null,
                    'browserBuf' => '',
                    'deadline'   => time() + 7200,
                ];
            }
        }

        $now = time();

        foreach ($this->sessions as $id => $sess) {
            // Expire timed-out sessions
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
            return; // Headers not yet complete
        }

        // Parse headers
        $lines   = explode("\r\n", $buf);
        $headers = ['_request_line' => $lines[0] ?? ''];
        foreach (array_slice($lines, 1) as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
        }

        // Extract token
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
        @unlink($sessionFile); // one-time use

        if (!$data || ($data['expires'] ?? 0) < time()) {
            fwrite($browser, "HTTP/1.1 403 Forbidden\r\nContent-Length: 0\r\n\r\n");
            $this->close($id);
            return;
        }

        // WebSocket handshake response to browser
        $wsKey  = $headers['sec-websocket-key'] ?? '';
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

        // Connect to Proxmox raw VNC TCP port
        $vnc = @stream_socket_client(
            "tcp://{$data['vnc_host']}:{$data['vnc_port']}",
            $errno, $errstr, 10
        );
        if (!$vnc) {
            fwrite($browser, "\x88\x00"); // WS close frame
            $this->close($id);
            return;
        }
        stream_set_blocking($vnc, false);

        $this->sessions[$id]['state'] = 'relay';
        $this->sessions[$id]['vnc']   = $vnc;
    }

    // ── Relay phase ───────────────────────────────────────────────────────────

    private function stepRelay(int $id, array $readable): void
    {
        $browser = $this->sessions[$id]['browser'];
        $vnc     = $this->sessions[$id]['vnc'];

        // Browser → VNC
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
                    fwrite($vnc, $raw);
                }
            }
        }

        // VNC → Browser
        if ($vnc && in_array($vnc, $readable, true)) {
            $data = @fread($vnc, 65536);
            if ($data === false || ($data === '' && feof($vnc))) {
                $this->close($id);
                return;
            }
            if ($data !== '') {
                fwrite($browser, $this->wrapBinaryFrame($data));
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

            if ($opcode === 0x8) return null; // Close

            $out .= $payload;
        }

        return $out;
    }

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
}
