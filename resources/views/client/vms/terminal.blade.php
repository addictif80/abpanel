<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-900">
<head>
    <meta charset="UTF-8">
    <title>Terminal — {{ $vm->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex flex-col bg-gray-900">

<div class="flex items-center justify-between px-4 py-2 bg-gray-800 border-b border-gray-700">
    <div class="flex items-center gap-3">
        <a href="{{ route('client.vms.show', $vm) }}" class="text-gray-400 hover:text-white text-sm">← {{ $vm->name }}</a>
        <span class="text-gray-600">|</span>
        <span class="text-green-400 font-mono text-sm">Terminal noVNC</span>
    </div>
    <div class="flex items-center gap-2">
        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
        <span class="text-gray-400 text-xs">Connecté</span>
    </div>
</div>

<div class="flex-1 relative">
    @if($ticket)
    <iframe
        src="{{ $proxmoxHost }}/?console=kvm&novnc=1&vmid={{ $vm->proxmox_vmid }}&vmname={{ urlencode($vm->name) }}&node={{ $vm->proxmox_node }}&resize=scale&ticket={{ urlencode($ticket) }}"
        class="w-full h-full border-0"
        allowfullscreen>
    </iframe>
    @else
    <div class="flex items-center justify-center h-full text-gray-400">
        <div class="text-center">
            <p class="text-lg mb-2">Impossible d'ouvrir le terminal</p>
            <p class="text-sm">Vérifiez que la VM est démarrée.</p>
            <a href="{{ route('client.vms.show', $vm) }}" class="mt-4 inline-block text-indigo-400 hover:underline">← Retour</a>
        </div>
    </div>
    @endif
</div>

</body>
</html>
