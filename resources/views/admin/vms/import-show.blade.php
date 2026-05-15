@extends('layouts.app')
@section('title', 'Importer VM ' . $vmInfo['vmid'])
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.vms.import.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Importer un VPS</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">
        Importer « {{ $vmInfo['name'] }} »
        <span class="text-base font-normal text-gray-400 ml-2">VMID {{ $vmInfo['vmid'] }} — {{ $vmInfo['node'] }}</span>
    </h1>
</div>

@if($errors->any())
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Form --}}
    <div class="lg:col-span-2">
        <form method="POST" action="{{ route('admin.vms.import.store', [$vmInfo['node'], $vmInfo['vmid']]) }}" class="space-y-5">
            @csrf
            <input type="hidden" name="vm_type" value="{{ $vmInfo['vm_type'] }}">

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                <h2 class="font-semibold text-gray-800">Assignation</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-500">*</span></label>
                    <select name="user_id" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">— Sélectionner un client —</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('user_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->full_name }} — {{ $client->email }}
                            @if($client->company) ({{ $client->company }})@endif
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom affiché au client <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $vmInfo['name']) }}" required maxlength="50"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix mensuel (€) <span class="text-red-500">*</span></label>
                    <input type="number" name="monthly_price" value="{{ old('monthly_price', '0.00') }}" min="0" step="0.01" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                <h2 class="font-semibold text-gray-800">Ressources <span class="text-xs font-normal text-gray-400">(pré-remplies depuis Proxmox)</span></h2>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">vCPU <span class="text-red-500">*</span></label>
                        <input type="number" name="cores" value="{{ old('cores', $vmInfo['cores']) }}" min="1" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">RAM (MB) <span class="text-red-500">*</span></label>
                        <input type="number" name="memory_mb" value="{{ old('memory_mb', $vmInfo['memory_mb']) }}" min="128" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Disque (GB)</label>
                        <input type="number" name="disk_gb" value="{{ old('disk_gb', $vmInfo['disk_gb']) }}" min="1"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                <h2 class="font-semibold text-gray-800">Réseau &amp; accès</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IP Tailscale</label>
                    <input type="text" name="tailscale_ip" value="{{ old('tailscale_ip') }}"
                        placeholder="100.x.x.x"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <p class="text-xs text-gray-400 mt-1">Nécessaire pour créer le sous-domaine dans Nginx Proxy Manager.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Statut actuel</label>
                    <select name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="running" {{ old('status', $vmInfo['status']) === 'running' ? 'selected' : '' }}>En cours d'exécution</option>
                        <option value="stopped" {{ old('status', $vmInfo['status']) === 'stopped' ? 'selected' : '' }}>Arrêtée</option>
                        <option value="hibernated" {{ old('status', $vmInfo['status']) === 'paused' ? 'selected' : '' }}>Hibernée</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                    class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    Importer et assigner
                </button>
                <a href="{{ route('admin.vms.import.index') }}" class="text-sm text-gray-500 hover:underline">Annuler</a>
            </div>
        </form>
    </div>

    {{-- Proxmox info card --}}
    <div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sticky top-6">
            <h2 class="font-semibold text-gray-800 mb-4">Informations Proxmox</h2>
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">VMID</dt>
                    <dd class="font-mono font-medium text-gray-800">{{ $vmInfo['vmid'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Nœud</dt>
                    <dd class="font-mono font-medium text-gray-800">{{ $vmInfo['node'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Nom Proxmox</dt>
                    <dd class="font-medium text-gray-800">{{ $vmInfo['name'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">vCPU</dt>
                    <dd class="font-medium text-gray-800">{{ $vmInfo['cores'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">RAM</dt>
                    <dd class="font-medium text-gray-800">
                        {{ $vmInfo['memory_mb'] >= 1024 ? round($vmInfo['memory_mb'] / 1024, 1) . ' GB' : $vmInfo['memory_mb'] . ' MB' }}
                    </dd>
                </div>
                @if($vmInfo['disk_gb'])
                <div class="flex justify-between">
                    <dt class="text-gray-500">Disque</dt>
                    <dd class="font-medium text-gray-800">{{ $vmInfo['disk_gb'] }} GB</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-gray-500">Statut</dt>
                    <dd>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $vmInfo['status'] === 'running' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $vmInfo['status'] }}
                        </span>
                    </dd>
                </div>
                @if($vmInfo['os_type'])
                <div class="flex justify-between">
                    <dt class="text-gray-500">OS</dt>
                    <dd class="font-medium text-gray-800">{{ $vmInfo['os_type'] }}</dd>
                </div>
                @endif
                @if($vmInfo['description'])
                <div class="pt-2 border-t border-gray-100">
                    <dt class="text-gray-500 mb-1">Description</dt>
                    <dd class="text-gray-600 text-xs whitespace-pre-line">{{ $vmInfo['description'] }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

</div>
@endsection
