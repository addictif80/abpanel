@extends('layouts.app')
@section('title', 'Sites')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Sites</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $accounts->total() }} compte(s) hébergement</p>
    </div>
    <a href="{{ route('admin.hosting.import.index') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
        Importer depuis CyberPanel
    </a>
</div>

@if(session('success'))
<div class="mb-5 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-5 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Domaine</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Offre</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Disque</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Prix/mois</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Renouvellement</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($accounts as $account)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3">
                    <div class="font-medium text-gray-800 font-mono text-sm">{{ $account->domain }}</div>
                    <div class="text-xs text-gray-400">{{ $account->cyberpanel_username }}</div>
                </td>
                <td class="px-5 py-3">
                    <a href="{{ route('admin.clients.show', $account->user) }}" class="text-indigo-600 hover:underline text-sm">
                        {{ $account->user->full_name }}
                    </a>
                </td>
                <td class="px-5 py-3 text-gray-600 text-sm">{{ $account->plan ?: '—' }}</td>
                <td class="px-5 py-3 text-gray-500 text-xs">
                    @if($account->disk_mb >= 1024)
                        {{ number_format($account->disk_mb / 1024, 1) }} GB
                    @else
                        {{ $account->disk_mb }} MB
                    @endif
                </td>
                <td class="px-5 py-3">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $account->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $account->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                </td>
                <td class="px-5 py-3 text-gray-800 font-medium">{{ number_format($account->monthly_price, 2) }}€</td>
                <td class="px-5 py-3 text-gray-400 text-xs">
                    {{ $account->next_renewal_at?->format('d/m/Y') ?? '—' }}
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-5 py-10 text-center text-gray-400">Aucun site</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($accounts->hasPages())
    <div class="px-5 py-4 border-t border-gray-50">{{ $accounts->links() }}</div>
    @endif
</div>
@endsection
