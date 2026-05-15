@extends('layouts.app')
@section('title', 'VPS')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">VPS</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $vms->total() }} VPS</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.vms.import.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Importer depuis Proxmox
        </a>
        <a href="{{ route('admin.vms.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            + Nouveau VPS
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">VPS</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Ressources</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Domaine</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Prix/mois</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($vms as $vm)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-800">{{ $vm->name }}</span>
                        @if(($vm->vm_type ?? 'qemu') === 'lxc')
                        <span class="px-1.5 py-0.5 rounded text-xs font-medium bg-teal-100 text-teal-700">LXC</span>
                        @else
                        <span class="px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">KVM</span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-400">VMID {{ $vm->proxmox_vmid }} — {{ $vm->proxmox_node }}</div>
                </td>
                <td class="px-5 py-3">
                    <a href="{{ route('admin.clients.show', $vm->user) }}" class="text-indigo-600 hover:underline text-sm">
                        {{ $vm->user->full_name }}
                    </a>
                </td>
                <td class="px-5 py-3 text-gray-600 text-xs">
                    {{ $vm->cores }} vCPU · {{ $vm->memory_mb }}MB · {{ $vm->disk_gb }}GB
                </td>
                <td class="px-5 py-3">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $vm->status === 'running' ? 'bg-green-100 text-green-700' :
                           ($vm->status === 'hibernated' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
                        {{ $vm->status }}
                    </span>
                </td>
                <td class="px-5 py-3 text-xs text-gray-500 font-mono">
                    {{ $vm->custom_domain ?: $vm->subdomain ?: '—' }}
                </td>
                <td class="px-5 py-3 text-gray-800 font-medium">{{ number_format($vm->monthly_price, 2) }}€</td>
                <td class="px-5 py-3 text-right">
                    <a href="{{ route('admin.vms.edit', $vm) }}" class="text-gray-500 hover:text-indigo-600 text-xs">Modifier</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-5 py-10 text-center text-gray-400">Aucun VPS</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($vms->hasPages())
    <div class="px-5 py-4 border-t border-gray-50">{{ $vms->links() }}</div>
    @endif
</div>
@endsection
