@extends('layouts.app')
@section('title', 'Importer un hébergement CyberPanel')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('admin.hosting.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Sites</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Importer un hébergement existant</h1>
        <p class="text-gray-500 text-sm mt-0.5">Tous les sites détectés sur CyberPanel. Assignez-en à vos clients.</p>
    </div>
    <a href="{{ route('admin.hosting.import.index') }}"
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
@php
    $total    = count($sites);
    $imported = collect($sites)->where('imported', true)->count();
    $pending  = $total - $imported;
@endphp
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-gray-900">{{ $total }}</div>
        <div class="text-xs text-gray-500 mt-0.5">Sites CyberPanel</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-amber-600">{{ $pending }}</div>
        <div class="text-xs text-gray-500 mt-0.5">Non importés</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-green-600">{{ $imported }}</div>
        <div class="text-xs text-gray-500 mt-0.5">Déjà dans le panel</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100" x-data="{ search: '' }">
    <div class="p-4 border-b border-gray-100">
        <input type="text" x-model="search" placeholder="Rechercher par domaine, propriétaire..."
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Domaine</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Propriétaire</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Offre</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Disque utilisé</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Panel</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($sites as $site)
                @php $domain = $site['domain'] ?? ''; @endphp
                <tr class="hover:bg-gray-50"
                    x-show="search === '' || '{{ strtolower($domain) }}'.includes(search.toLowerCase()) || '{{ strtolower($site['owner'] ?? '') }}'.includes(search.toLowerCase())">
                    <td class="px-5 py-3">
                        <div class="font-medium text-gray-800 font-mono">{{ $domain }}</div>
                    </td>
                    <td class="px-5 py-3 text-gray-600">{{ $site['owner'] ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-500 text-xs">{{ $site['package'] ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-500 text-xs">
                        @php $diskGb = isset($site['diskUsage']) ? round($site['diskUsage'], 2) : null; @endphp
                        {{ $diskGb !== null ? $diskGb . ' GB' : '—' }}
                    </td>
                    <td class="px-5 py-3">
                        @if($site['imported'])
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-600">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Importé
                        </span>
                        @else
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-600">
                            Non importé
                        </span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        @if(!$site['imported'])
                        <a href="{{ route('admin.hosting.import.show', urlencode($domain)) }}"
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
                    <td colspan="6" class="px-5 py-10 text-center text-gray-400">Aucun site trouvé sur CyberPanel</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
