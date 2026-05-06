@extends('layouts.app')
@section('title', 'Importer une VM Proxmox')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('admin.vms.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← VMs</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Importer une VM existante</h1>
        <p class="text-gray-500 text-sm mt-0.5">Toutes les VMs détectées sur Proxmox. Assignez-en à vos clients.</p>
    </div>
    <a href="{{ route('admin.vms.import.index') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        Rafraîchir
    </a>
</div>

@if(session('error') || $error)
<div class="mb-5 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    {{ session('error') ?? $error }}
</div>
@endif
@if(session('success'))
<div class="mb-5 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
@endif

@if(!$error)
{{-- Summary badges --}}
@php
    $total = count($proxmoxVms);
    $imported = collect($proxmoxVms)->where('imported', true)->count();
    $pending = $total - $imported;
@endphp
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-gray-900">{{ $total }}</div>
        <div class="text-xs text-gray-500 mt-0.5">VMs Proxmox</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-amber-600">{{ $pending }}</div>
        <div class="text-xs text-gray-500 mt-0.5">Non importées</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-green-600">{{ $imported }}</div>
        <div class="text-xs text-gray-500 mt-0.5">Déjà dans le panel</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100" x-data="{ search: '', node: '' }">
    <div class="p-4 border-b border-gray-100 flex gap-3">
        <input type="text" x-model="search" placeholder="Rechercher par nom, VMID..."
            class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        @php $nodes = collect($proxmoxVms)->pluck('node')->unique()->sort(); @endphp
        @if($nodes->count() > 1)
        <select x-model="node" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <option value="">Tous les nœuds</option>
            @foreach($nodes as $n)
            <option value="{{ $n }}">{{ $n }}</option>
            @endforeach
        </select>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">VM</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Nœud</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Ressources</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Panel</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($proxmoxVms as $vm)
                <tr class="hover:bg-gray-50"
                    x-show="
                        (search === '' || '{{ strtolower($vm['name'] ?? '') }}'.includes(search.toLowerCase()) || '{{ $vm['vmid'] }}'.includes(search))
                        && (node === '' || node === '{{ $vm['node'] }}')
                    ">
                    <td class="px-5 py-3">
                        <div class="font-medium text-gray-800">{{ $vm['name'] ?? 'vm-' . $vm['vmid'] }}</div>
                        <div class="text-xs text-gray-400 font-mono">VMID {{ $vm['vmid'] }}</div>
                    </td>
                    <td class="px-5 py-3 text-gray-600 text-sm font-mono">{{ $vm['node'] }}</td>
                    <td class="px-5 py-3 text-gray-500 text-xs">
                        @if(isset($vm['maxcpu']) || isset($vm['maxmem']))
                            {{ isset($vm['maxcpu']) ? $vm['maxcpu'] . ' vCPU' : '' }}
                            {{ isset($vm['maxmem']) ? ' · ' . round($vm['maxmem'] / 1024 / 1024) . ' MB RAM' : '' }}
                            {{ isset($vm['maxdisk']) ? ' · ' . round($vm['maxdisk'] / 1024 / 1024 / 1024) . ' GB' : '' }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        @php $s = $vm['status'] ?? 'unknown'; @endphp
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $s === 'running' ? 'bg-green-100 text-green-700' : ($s === 'paused' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
                            @if($s === 'running')<span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>@endif
                            {{ $s }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        @if($vm['imported'])
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-600">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Importée
                        </span>
                        @else
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-600">
                            Non importée
                        </span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        @if(!$vm['imported'])
                        <a href="{{ route('admin.vms.import.show', [$vm['node'], $vm['vmid']]) }}"
                           class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition">
                            Importer →
                        </a>
                        @else
                        <span class="text-xs text-gray-300">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-gray-400">Aucune VM trouvée sur Proxmox</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
