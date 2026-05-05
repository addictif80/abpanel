@extends('layouts.app')
@section('title', 'Factures')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Factures</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $invoices->total() }} facture(s)</p>
    </div>
    <a href="{{ route('admin.invoices.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        + Nouvelle facture
    </a>
</div>

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="p-4 border-b border-gray-50">
        <form method="GET" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Numéro, client..."
                class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">Tous les statuts</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Payée</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulée</option>
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
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Créée le</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Échéance</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($invoices as $invoice)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-mono text-xs font-medium text-gray-800">{{ $invoice->number }}</td>
                    <td class="px-5 py-3">
                        <div class="font-medium text-gray-800">{{ $invoice->user?->full_name ?? '—' }}</div>
                        <div class="text-xs text-gray-400">{{ $invoice->user?->email }}</div>
                    </td>
                    <td class="px-5 py-3 font-semibold text-gray-800">{{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</td>
                    <td class="px-5 py-3">
                        @php
                            $colors = ['pending' => 'bg-amber-100 text-amber-700', 'paid' => 'bg-green-100 text-green-700', 'cancelled' => 'bg-gray-100 text-gray-500'];
                            $labels = ['pending' => 'En attente', 'paid' => 'Payée', 'cancelled' => 'Annulée'];
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$invoice->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $labels[$invoice->status] ?? $invoice->status }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-400 text-xs">{{ $invoice->created_at->format('d/m/Y') }}</td>
                    <td class="px-5 py-3 text-gray-400 text-xs">{{ $invoice->due_at ? $invoice->due_at->format('d/m/Y') : '—' }}</td>
                    <td class="px-5 py-3 text-right space-x-2">
                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-indigo-600 hover:underline text-xs">Voir</a>
                        @if($invoice->status === 'pending')
                        <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-green-600 hover:underline text-xs">Marquer payée</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-10 text-center text-gray-400">Aucune facture</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($invoices->hasPages())
    <div class="px-5 py-4 border-t border-gray-50">{{ $invoices->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
