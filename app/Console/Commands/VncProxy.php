<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Single-session WebSocket-to-VNC-TCP proxy (websockify-style).
 *
 * The browser connects via WebSocket (noVNC); this command connects
 * to Proxmox's raw VNC TCP port and relays frames.  The raw VNC port
 * does not need HTTP/cookie auth — VNC password auth uses the ticket
 * returned by vncproxy.
 */
class VncProxy extends Command
{
    protected $signature = 'vnc:proxy
        {port      : Local TCP port to listen on}
        {vnc_host  : Proxmox node hostname/IP}
        {vnc_port  : VNC TCP port returned by Proxmox vncproxy}
        {token     : One-time auth token expected from the browser}';

    protected $description = 'Run a single-session VNC WebSocket proxy';

    public function handle(): int
    {
        set_time_limit(0);

        $listenPort = (int) $this->argument('port');
        $vncHost    = $this->argument('vnc_host');
        $vncPort    = (int) $this->argument('vnc_port');
        $token      = $this->argument('token');

        $server = @stream_socket_server("tcp://0.0.0.0:{$listenPort}", $errno, $errstr);
        if (!$server) {
            return 1;
        }

        $client = @stream_socket_accept($server, 30);
        fclose($server);
        if (!$client) {
            return 1;
        }

        $headers = $this->readHttpHeaders($client);
        if (!$headers) {
            fclose($client);
            return 1;
        }

        // Validate one-time token from query string
        $requestToken = $headers['_token'] ?? '';
        if (!hash_equals($token, $requestToken)) {
            fwrite($client, "HTTP/1.1 403 Forbidden\r\nContent-Length: 0\r\n\r\n");
            fclose($client);
            return 1;
        }

        if (!$this->sendWsHandshake($client, $headers)) {
            fclose($client);
            return 1;
        }

        // Connect to Proxmox raw VNC TCP port (no HTTP auth needed)
        $vnc = @stream_socket_client("tcp://{$vncHost}:{$vncPort}", $errno, $errstr, 10);
        if (!$vnc) {
            fwrite($client, $this->wsCloseFrame());
            fclose($client);
            return 1;
        }

        $this->relay($client, $vnc);

        @fclose($client);
        @fclose($vnc);
        return 0;
    }

    // ── WebSocket helpers ─────────────────────────────────────────────────────

    private function readHttpHeaders($socket): ?array
    {
        stream_set_blocking($socket, true);
        stream_set_timeout($socket, 30);

        $raw    = '';
        $cutoff = 16384;

        while (!str_ends_with($raw, "\r\n\r\n")) {
            $ch = fread($socket, 1);
            if ($ch === false || $ch === '') {
                return null;
            }
            $raw .= $ch;
            if (strlen($raw) > $cutoff) {
                return null;
            }
        }

        $lines   = explode("\r\n", $raw);
        $headers = ['_raw' => $raw, '_request_line' => $lines[0] ?? ''];

        if (preg_match('/[?&]token=([^& ]+)/', $headers['_request_line'], $m)) {
            $headers['_token'] = urldecode($m[1]);
        }

        foreach (array_slice($lines, 1) as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
        }

        return $headers;
    }

    private function sendWsHandshake($socket, array $headers): bool
    {
        $key = $headers['sec-websocket-key'] ?? '';
        if (!$key) {
            return false;
        }

        $accept   = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        $protocol = $headers['sec-websocket-protocol'] ?? '';

        $resp  = "HTTP/1.1 101 Switching Protocols\r\n";
        $resp .= "Upgrade: websocket\r\n";
        $resp .= "Connection: Upgrade\r\n";
        $resp .= "Sec-WebSocket-Accept: {$accept}\r\n";
        if ($protocol) {
            $resp .= "Sec-WebSocket-Protocol: " . trim(explode(',', $protocol)[0]) . "\r\n";
        }
        $resp .= "\r\n";

        return fwrite($socket, $resp) !== false;
    }

    private function wsCloseFrame(): string
    {
        return "\x88\x00";
    }

    // ── Frame relay ───────────────────────────────────────────────────────────

    private function relay($browser, $vnc): void
    {
        stream_set_blocking($browser, false);
        stream_set_blocking($vnc, false);

        $browserBuf = '';
        $deadline   = time() + 7200; // 2-hour max session

        while (time() < $deadline) {
            $read  = [$browser, $vnc];
            $write = $except = null;

            if (stream_select($read, $write, $except, 1, 0) === false) {
                break;
            }

            foreach ($read as $sock) {
                $data = @fread($sock, 65536);
                if ($data === false || ($data === '' && feof($sock))) {
                    return;
                }
                if ($data === '') {
                    continue;
                }

                if ($sock === $browser) {
                    // WebSocket frames from browser → unwrap → write raw VNC
                    $browserBuf .= $data;
                    $raw = $this->unwrapWsFrames($browserBuf);
                    if ($raw === null) {
                        return; // Close frame received
                    }
                    if ($raw !== '') {
                        fwrite($vnc, $raw);
                    }
                } else {
                    // Raw VNC data → wrap in WebSocket binary frame → browser
                    fwrite($browser, $this->wrapBinaryFrame($data));
                }
            }
        }
    }

    /**
     * Consume all complete WebSocket frames in $buffer, return concatenated
     * payloads (unmasked).  Returns null on Close opcode.
     */
    private function unwrapWsFrames(string &$buf): ?string
    {
        $out = '';

        while (strlen($buf) >= 2) {
            $b0 = ord($buf[0]);
            $b1 = ord($buf[1]);

            $opcode  = $b0 & 0x0F;
            $masked  = ($b1 & 0x80) !== 0;
            $payLen  = $b1 & 0x7F;
            $offset  = 2;

            if ($payLen === 126) {
                if (strlen($buf) < 4) {
                    break;
                }
                $payLen = (ord($buf[2]) << 8) | ord($buf[3]);
                $offset = 4;
            } elseif ($payLen === 127) {
                if (strlen($buf) < 10) {
                    break;
                }
                $payLen = 0;
                for ($i = 2; $i < 10; $i++) {
                    $payLen = ($payLen << 8) | ord($buf[$i]);
                }
                $offset = 10;
            }

            $maskLen = $masked ? 4 : 0;
            $total   = $offset + $maskLen + $payLen;

            if (strlen($buf) < $total) {
                break;
            }

            $maskKey = $masked ? substr($buf, $offset, 4) : '';
            $payload = substr($buf, $offset + $maskLen, $payLen);
            $buf     = substr($buf, $total);

            if ($masked) {
                for ($i = 0; $i < strlen($payload); $i++) {
                    $payload[$i] = chr(ord($payload[$i]) ^ ord($maskKey[$i % 4]));
                }
            }

            if ($opcode === 0x8) {
                return null; // Close
            }

            $out .= $payload;
        }

        return $out;
    }

    /** Wrap raw bytes in a WebSocket binary frame (server→client, no mask). */
    private function wrapBinaryFrame(string $payload): string
    {
        $len   = strlen($payload);
        $frame = "\x82"; // FIN=1, binary

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
