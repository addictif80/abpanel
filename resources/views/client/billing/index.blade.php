@extends('layouts.app')
@section('title', 'Facturation')
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Facturation</h1>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="text-2xl font-bold text-green-600">{{ number_format($totalPaid, 2) }}€</div>
        <div class="text-xs text-gray-500 mt-1">Total payé</div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="text-2xl font-bold text-amber-600">{{ number_format($pendingAmount, 2) }}€</div>
        <div class="text-xs text-gray-500 mt-1">En attente</div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="text-2xl font-bold text-gray-700">{{ $invoices->total() }}</div>
        <div class="text-xs text-gray-500 mt-1">Facture(s) au total</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Numéro</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Montant</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($invoices as $invoice)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3 font-mono text-gray-800">{{ $invoice->number }}</td>
                <td class="px-5 py-3 text-gray-600">{{ $invoice->created_at->format('d/m/Y') }}</td>
                <td class="px-5 py-3 font-semibold text-gray-800">{{ number_format($invoice->total, 2) }}€</td>
                <td class="px-5 py-3">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-700' :
                           ($invoice->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                        {{ match($invoice->status) {
                            'paid' => 'Payée',
                            'failed' => 'Échouée',
                            'refunded' => 'Remboursée',
                            default => 'En attente'
                        } }}
                    </span>
                </td>
                <td class="px-5 py-3 text-right">
                    <a href="{{ route('client.billing.invoice', $invoice) }}" class="text-indigo-600 hover:underline text-xs mr-3">Voir</a>
                    <a href="{{ route('client.billing.invoice.download', $invoice) }}" target="_blank" class="text-gray-500 hover:underline text-xs">Imprimer</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">Aucune facture</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($invoices->hasPages())
    <div class="px-5 py-4 border-t border-gray-50">{{ $invoices->links() }}</div>
    @endif
</div>
@endsection
