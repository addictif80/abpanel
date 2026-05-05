@extends('layouts.app')
@section('title', 'Nouvelle VM')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.vms.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← VMs</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Créer une VM</h1>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-2xl">
    <form method="POST" action="{{ route('admin.vms.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Client *</label>
            <select name="user_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">— Sélectionner un client —</option>
                @foreach($clients as $client)
                <option value="{{ $client->id }}" {{ old('user_id') == $client->id ? 'selected' : '' }}>
                    {{ $client->full_name }} ({{ $client->email }})
                </option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la VM *</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="ex: vm-client-web"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nœud Proxmox *</label>
                <input type="text" name="proxmox_node" value="{{ old('proxmox_node', \App\Models\Setting::get('proxmox_node', 'pve')) }}" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">vCPU *</label>
                <input type="number" name="cores" value="{{ old('cores', 2) }}" min="1" max="32" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">RAM (MB) *</label>
                <input type="number" name="memory_mb" value="{{ old('memory_mb', 2048) }}" min="512" step="512" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Disque (GB) *</label>
                <input type="number" name="disk_gb" value="{{ old('disk_gb', 20) }}" min="5" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">IP Tailscale</label>
                <input type="text" name="tailscale_ip" value="{{ old('tailscale_ip') }}" placeholder="100.x.x.x"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                <p class="text-xs text-gray-400 mt-1">Utilisée pour créer la règle NPM automatiquement.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prix mensuel (€) *</label>
                <input type="number" name="monthly_price" value="{{ old('monthly_price', 0) }}" step="0.01" min="0" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>
        <div class="pt-4 border-t border-gray-100 flex gap-3">
            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                Créer la VM
            </button>
            <a href="{{ route('admin.vms.index') }}" class="px-5 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition">
                Annuler
            </a>
        </div>
    </form>
</div>
@endsection
