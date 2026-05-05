@extends('layouts.app')
@section('title', 'Facture ' . $invoice->number)
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('admin.invoices.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Factures</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $invoice->number }}</h1>
    </div>
    <div class="flex items-center gap-3">
        @if($invoice->status === 'pending')
        <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice) }}">
            @csrf
            <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition">
                Marquer payée
            </button>
        </form>
        @endif
        <form method="POST" action="{{ route('admin.invoices.destroy', $invoice) }}"
              onsubmit="return confirm('Supprimer cette facture ?')">
            @csrf @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 text-sm font-semibold rounded-lg hover:bg-red-100 transition">
                Supprimer
            </button>
        </form>
    </div>
</div>

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Lignes</h2>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-2 text-gray-500 font-medium">Description</th>
                        <th class="text-right py-2 text-gray-500 font-medium">Qté</th>
                        <th class="text-right py-2 text-gray-500 font-medium">Prix unit.</th>
                        <th class="text-right py-2 text-gray-500 font-medium">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items ?? [] as $item)
                    <tr class="border-b border-gray-50">
                        <td class="py-2.5 text-gray-800">{{ $item['description'] }}</td>
                        <td class="py-2.5 text-right text-gray-600">{{ $item['quantity'] }}</td>
                        <td class="py-2.5 text-right text-gray-600">{{ number_format($item['unit_price'], 2) }} €</td>
                        <td class="py-2.5 text-right font-medium text-gray-800">{{ number_format($item['total'], 2) }} €</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="py-3 text-right font-semibold text-gray-700">Total</td>
                        <td class="py-3 text-right font-bold text-lg text-gray-900">{{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>

    <div class="space-y-5">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Informations</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Statut</dt>
                    <dd>
                        @php $colors = ['pending' => 'bg-amber-100 text-amber-700', 'paid' => 'bg-green-100 text-green-700', 'cancelled' => 'bg-gray-100 text-gray-500']; @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$invoice->status] ?? '' }}">
                            {{ ['pending' => 'En attente', 'paid' => 'Payée', 'cancelled' => 'Annulée'][$invoice->status] ?? $invoice->status }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between"><dt class="text-gray-500">Créée le</dt><dd class="font-medium">{{ $invoice->created_at->format('d/m/Y') }}</dd></div>
                @if($invoice->due_at)
                <div class="flex justify-between"><dt class="text-gray-500">Échéance</dt><dd class="font-medium">{{ $invoice->due_at->format('d/m/Y') }}</dd></div>
                @endif
                @if($invoice->paid_at)
                <div class="flex justify-between"><dt class="text-gray-500">Payée le</dt><dd class="font-medium text-green-600">{{ \Carbon\Carbon::parse($invoice->paid_at)->format('d/m/Y') }}</dd></div>
                @endif
                @if($invoice->stripe_payment_intent_id)
                <div class="pt-2 border-t border-gray-50">
                    <dt class="text-gray-500 mb-1">Stripe Intent</dt>
                    <dd class="font-mono text-xs text-gray-400 break-all">{{ $invoice->stripe_payment_intent_id }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Client</h2>
            @if($invoice->user)
            <div class="text-sm space-y-1">
                <p class="font-medium text-gray-800">{{ $invoice->user->full_name }}</p>
                <p class="text-gray-500">{{ $invoice->user->email }}</p>
                @if($invoice->user->company)<p class="text-gray-500">{{ $invoice->user->company }}</p>@endif
            </div>
            <a href="{{ route('admin.clients.show', $invoice->user) }}" class="mt-3 text-xs text-indigo-600 hover:underline block">Voir le client →</a>
            @else
            <p class="text-sm text-gray-400">Client supprimé</p>
            @endif
        </div>
    </div>
</div>
@endsection
