@extends('layouts.app')
@section('title', 'Facture ' . $invoice->number)
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('client.billing.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Facturation</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Facture {{ $invoice->number }}</h1>
    </div>
    <div class="flex gap-2">
        @if(!$invoice->isPaid() && $stripeKey)
        <a href="{{ route('client.billing.invoice.pay', $invoice) }}"
           class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition">
            Payer en ligne
        </a>
        @endif
        <a href="{{ route('client.billing.invoice.download', $invoice) }}"
           class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            Télécharger PDF
        </a>
        <a href="{{ route('client.billing.invoice.facturx', $invoice) }}"
           class="px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition"
           title="Facture électronique structurée (Factur-X / EN 16931)">
            XML Factur-X
        </a>
    </div>
</div>

@foreach(['success','error','info'] as $type)
@if(session($type))
@php $colors=['success'=>'bg-green-50 border-green-200 text-green-700','error'=>'bg-red-50 border-red-200 text-red-700','info'=>'bg-blue-50 border-blue-200 text-blue-700']; @endphp
<div class="mb-4 px-4 py-3 {{ $colors[$type] }} border rounded-lg text-sm">{{ session($type) }}</div>
@endif
@endforeach

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-2xl">
    <div class="flex justify-between items-start mb-8">
        <div>
            <div class="text-2xl font-bold text-indigo-600">{{ \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('app_name', config('app.name')) }}</div>
        </div>
        <div class="text-right">
            <div class="text-lg font-bold text-gray-900">{{ $invoice->number }}</div>
            <div class="text-sm text-gray-500">Émise le {{ $invoice->created_at->format('d/m/Y') }}</div>
            @if($invoice->paid_at)
            <div class="text-sm text-green-600">Payée le {{ $invoice->paid_at->format('d/m/Y') }}</div>
            @endif
            @if($invoice->due_at && !$invoice->isPaid())
            <div class="text-sm {{ $invoice->due_at->isPast() ? 'text-red-500 font-medium' : 'text-gray-400' }}">
                Échéance : {{ $invoice->due_at->format('d/m/Y') }}
            </div>
            @endif
        </div>
    </div>

    <div class="mb-6">
        <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Facturé à</div>
        <div class="text-gray-800 font-medium">{{ $invoice->user->full_name }}</div>
        <div class="text-gray-500 text-sm">{{ $invoice->user->email }}</div>
        @if($invoice->user->company)
        <div class="text-gray-500 text-sm">{{ $invoice->user->company }}</div>
        @endif
    </div>

    <table class="w-full text-sm mb-6">
        <thead class="bg-gray-50">
            <tr>
                <th class="text-left px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Description</th>
                <th class="text-right px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Montant</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($invoice->items ?? [] as $item)
            <tr>
                <td class="px-3 py-2 text-gray-700">{{ $item['description'] ?? '—' }}</td>
                <td class="px-3 py-2 text-right text-gray-800 font-medium">{{ number_format($item['total'] ?? $item['amount'] ?? 0, 2) }}€</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="border-t border-gray-100 pt-4 space-y-1 text-sm">
        <div class="flex justify-between text-gray-600"><span>Sous-total HT</span><span>{{ number_format($invoice->subtotal, 2) }}€</span></div>
        <div class="flex justify-between text-gray-600"><span>TVA</span><span>{{ number_format($invoice->tax, 2) }}€</span></div>
        <div class="flex justify-between font-bold text-gray-900 text-base pt-1 border-t border-gray-100">
            <span>Total TTC</span><span>{{ number_format($invoice->total, 2) }}€</span>
        </div>
    </div>

    <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
        <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold
            {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
            {{ $invoice->isPaid() ? 'Payée' : 'En attente de paiement' }}
        </span>
        @if(!$invoice->isPaid() && $stripeKey)
        <a href="{{ route('client.billing.invoice.pay', $invoice) }}"
           class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition">
            Payer en ligne →
        </a>
        @endif
    </div>
</div>
@endsection
