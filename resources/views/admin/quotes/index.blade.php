@extends('layouts.app')
@section('title', 'Devis')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Devis</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $quotes->total() }} devis</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.quotes.templates') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
            Modèles
        </a>
        <a href="{{ route('admin.quotes.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            + Nouveau devis
        </a>
    </div>
</div>

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="p-4 border-b border-gray-50">
        <form method="GET" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Numéro, client, objet..."
                class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">Tous les statuts</option>
                @foreach(['draft' => 'Brouillon', 'sent' => 'Envoyé', 'viewed' => 'Consulté', 'accepted' => 'Accepté', 'refused' => 'Refusé', 'expired' => 'Expiré', 'invoiced' => 'Facturé', 'cancelled' => 'Annulé'] as $val => $label)
                <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200">Filtrer</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Numéro</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Objet</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Expiration</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($quotes as $quote)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-mono text-xs font-medium text-gray-800">{{ $quote->number }}</td>
                    <td class="px-5 py-3">
                        <div class="font-medium text-gray-800">{{ $quote->user?->full_name ?? '—' }}</div>
                        <div class="text-xs text-gray-400">{{ $quote->user?->email }}</div>
                    </td>
                    <td class="px-5 py-3 text-gray-600 max-w-xs truncate">{{ $quote->subject ?: '—' }}</td>
                    <td class="px-5 py-3 font-semibold text-gray-800">{{ number_format($quote->total, 2) }} {{ $quote->currency }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $quote->statusColor() }}">
                            {{ $quote->statusLabel() }}
                        </span>
                        @if($quote->opened_at)
                            <span class="ml-1 text-xs text-indigo-400" title="Consulté le {{ $quote->last_viewed_at->format('d/m/Y H:i') }}">👁</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-xs {{ $quote->isExpired() ? 'text-red-500 font-medium' : 'text-gray-400' }}">
                        {{ $quote->expires_at ? $quote->expires_at->format('d/m/Y') : '—' }}
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('admin.quotes.show', $quote) }}" class="text-indigo-600 hover:underline text-xs">Voir</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-10 text-center text-gray-400">Aucun devis. <a href="{{ route('admin.quotes.create') }}" class="text-indigo-600 hover:underline">Créer le premier.</a></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($quotes->hasPages())
    <div class="px-5 py-4 border-t border-gray-50">{{ $quotes->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
