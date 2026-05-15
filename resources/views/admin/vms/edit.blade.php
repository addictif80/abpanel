@extends('layouts.app')
@section('title', 'Modifier ' . $vm->name)
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.vms.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← VMs</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $vm->name }}</h1>
    <p class="text-gray-500 text-sm">VMID {{ $vm->proxmox_vmid }} — {{ $vm->proxmox_node }}</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-2xl">
    <form method="POST" action="{{ route('admin.vms.update', $vm) }}" class="space-y-4">
        @csrf @method('PUT')
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
            <input type="text" name="name" value="{{ old('name', $vm->name) }}" required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">IP Tailscale</label>
                <input type="text" name="tailscale_ip" value="{{ old('tailscale_ip', $vm->tailscale_ip) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Domaine personnalisé</label>
                <input type="text" name="custom_domain" value="{{ old('custom_domain', $vm->custom_domain) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    @foreach(['running', 'stopped', 'hibernated'] as $s)
                    <option value="{{ $s }}" {{ $vm->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prix mensuel (€)</label>
                <input type="number" name="monthly_price" value="{{ old('monthly_price', $vm->monthly_price) }}" step="0.01" min="0"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-4 text-xs text-gray-500 space-y-1">
            <div><strong>Client :</strong> {{ $vm->user->full_name }}</div>
            <div><strong>Ressources :</strong> {{ $vm->cores }} vCPU · {{ $vm->memory_mb }}MB RAM · {{ $vm->disk_gb }}GB</div>
            <div><strong>Sous-domaine :</strong> {{ $vm->subdomain ?: '—' }}</div>
        </div>

        <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
            <div class="flex gap-3">
                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    Enregistrer
                </button>
                <a href="{{ route('admin.vms.index') }}" class="px-5 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition">
                    Annuler
                </a>
            </div>
            <form method="POST" action="{{ route('admin.vms.destroy', $vm) }}"
                onsubmit="return confirm('Supprimer ce VPS de Proxmox ? Cette action est irréversible.')">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 text-sm font-semibold rounded-lg hover:bg-red-100 transition">
                    Supprimer
                </button>
            </form>
        </div>
    </form>
</div>
@endsection
