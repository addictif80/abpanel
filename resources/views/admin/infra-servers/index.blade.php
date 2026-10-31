@extends('layouts.app')
@section('title', 'Serveurs')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Serveurs</h1>
        <p class="text-gray-500 text-sm mt-0.5">Proxmox, CyberPanel et Synology — plusieurs serveurs par type possibles.</p>
    </div>
</div>

@if(session('success'))
<div class="mb-5 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-5 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
@endif

@php
$labels = ['proxmox' => 'Proxmox', 'cyberpanel' => 'CyberPanel', 'synology' => 'Synology'];
@endphp

<div class="space-y-6">
    @foreach(['proxmox', 'cyberpanel', 'synology'] as $type)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">{{ $labels[$type] }}</h2>
            <a href="{{ route('admin.infra-servers.create', ['type' => $type]) }}"
               class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition">
                + Ajouter un serveur {{ $labels[$type] }}
            </a>
        </div>
        <div class="divide-y divide-gray-50" x-data="{}">
            @forelse($servers[$type] ?? [] as $server)
            <div class="flex items-center justify-between px-5 py-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-800">{{ $server->label }}</span>
                        @if($server->is_default)
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">Par défaut</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $server->get('host') }}</p>
                </div>
                <div class="flex items-center gap-2" x-data="{ loading: false, message: '', success: null }">
                    <span x-show="message" :class="success ? 'text-green-600' : 'text-red-600'" class="text-xs font-medium" x-text="message"></span>
                    <button type="button" :disabled="loading"
                        @click="loading = true; fetch('{{ route('admin.infra-servers.test', $server) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } }).then(r => r.json()).then(d => { loading = false; success = d.success; message = d.message; })"
                        class="px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-200 transition disabled:opacity-50">
                        <span x-text="loading ? 'Test...' : 'Tester'"></span>
                    </button>
                    <a href="{{ route('admin.infra-servers.edit', $server) }}"
                       class="px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-200 transition">
                        Modifier
                    </a>
                    <form method="POST" action="{{ route('admin.infra-servers.destroy', $server) }}"
                          onsubmit="return confirm('Supprimer le serveur « {{ addslashes($server->label) }} » ?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-3 py-1.5 bg-red-50 text-red-600 text-xs font-semibold rounded-lg hover:bg-red-100 transition">
                            Supprimer
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-gray-400 text-sm">Aucun serveur {{ $labels[$type] }} configuré.</div>
            @endforelse
        </div>
    </div>
    @endforeach
</div>
@endsection
