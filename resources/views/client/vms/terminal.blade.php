<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-900">
<head>
    <meta charset="UTF-8">
    <title>Terminal — {{ $vm->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #screen { width: 100%; height: 100%; }
        #screen canvas { display: block; }
    </style>
</head>
<body class="h-full flex flex-col bg-gray-900">

<div class="flex items-center justify-between px-4 py-2 bg-gray-800 border-b border-gray-700 shrink-0">
    <div class="flex items-center gap-3">
        <a href="{{ route('client.vms.show', $vm) }}" class="text-gray-400 hover:text-white text-sm">← {{ $vm->name }}</a>
        <span class="text-gray-600">|</span>
        <span class="text-green-400 font-mono text-sm">Terminal noVNC</span>
    </div>
    <div id="status" class="flex items-center gap-2">
        <span class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></span>
        <span class="text-gray-400 text-xs">Connexion…</span>
    </div>
</div>

<div id="screen" class="flex-1 overflow-hidden"></div>

<script type="module">
import RFB from 'https://cdn.jsdelivr.net/npm/@novnc/novnc@1.5.0/core/rfb.js';

const proxyPort = {{ $proxyPort }};
const token     = {{ json_encode($token) }};
const ticket    = {{ json_encode($vncTicket) }};
const wsScheme  = location.protocol === 'https:' ? 'wss' : 'ws';
const wsUrl     = `${wsScheme}://${location.hostname}:${proxyPort}/?token=${encodeURIComponent(token)}`;

const statusEl = document.getElementById('status');

function setStatus(color, pulse, text) {
    statusEl.innerHTML = `<span class="w-2 h-2 ${color} rounded-full ${pulse}"></span><span class="text-gray-400 text-xs">${text}</span>`;
}

try {
    const rfb = new RFB(document.getElementById('screen'), wsUrl, {
        credentials: { password: ticket },
    });

    rfb.scaleViewport = true;
    rfb.resizeSession = true;

    rfb.addEventListener('connect', () => setStatus('bg-green-500', 'animate-pulse', 'Connecté'));
    rfb.addEventListener('disconnect', (e) => {
        setStatus('bg-red-500', '', e.detail.clean ? 'Déconnecté' : 'Connexion perdue');
    });
    rfb.addEventListener('credentialsrequired', () => rfb.sendCredentials({ password: ticket }));
} catch (err) {
    setStatus('bg-red-500', '', 'Erreur : ' + err.message);
}
</script>

</body>
</html>
