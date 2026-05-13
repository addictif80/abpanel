@extends('layouts.app')
@section('title', $quote->number)
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="flex items-start justify-between mb-6">
    <div>
        <a href="{{ route('client.quotes.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Mes devis</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $quote->number }}</h1>
        @if($quote->subject)<p class="text-gray-500 text-sm mt-1">{{ $quote->subject }}</p>@endif
    </div>
    <div class="flex gap-2">
        <a href="{{ route('client.quotes.download-pdf', $quote) }}"
           class="px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
            Télécharger PDF
        </a>
        <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-semibold {{ $quote->statusColor() }}">
            {{ $quote->statusLabel() }}
        </span>
    </div>
</div>

@foreach(['success', 'error', 'info'] as $type)
@if(session($type))
@php $colors = ['success' => 'bg-green-50 border-green-200 text-green-700', 'error' => 'bg-red-50 border-red-200 text-red-700', 'info' => 'bg-blue-50 border-blue-200 text-blue-700']; @endphp
<div class="mb-4 px-4 py-3 {{ $colors[$type] }} border rounded-lg text-sm">{{ session($type) }}</div>
@endif
@endforeach

@if($quote->isExpired())
<div class="mb-5 px-4 py-3 bg-orange-50 border border-orange-200 text-orange-700 rounded-lg text-sm">
    Ce devis a expiré le {{ $quote->expires_at->format('d/m/Y') }}. Veuillez nous contacter pour obtenir un nouveau devis.
</div>
@elseif($quote->expires_at && $quote->isPending())
<div class="mb-5 px-4 py-3 bg-amber-50 border border-amber-100 text-amber-700 rounded-lg text-sm">
    Ce devis est valable jusqu'au <strong>{{ $quote->expires_at->format('d/m/Y') }}</strong>.
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    <div class="lg:col-span-2 space-y-5">

        {{-- Items --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Prestation</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Qté</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Prix unit.</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($quote->items as $item)
                    <tr>
                        <td class="px-5 py-3">
                            <div class="font-medium text-gray-800">{{ $item->description }}</div>
                            @if($item->details)<div class="text-xs text-gray-400">{{ $item->details }}</div>@endif
                            @if($item->discount_amount > 0)
                            <div class="text-xs text-green-600">
                                Remise : {{ $item->discount_type === 'percent' ? $item->discount_amount . '%' : number_format($item->discount_amount, 2) . ' €' }}
                            </div>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right text-gray-600">{{ $item->quantity }} {{ $item->unit }}</td>
                        <td class="px-5 py-3 text-right text-gray-600">{{ number_format($item->unit_price, 2) }} €</td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-800">{{ number_format($item->total, 2) }} €</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="px-5 py-4 border-t border-gray-50 flex justify-end">
                <div class="w-64 space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Sous-total</span>
                        <span>{{ number_format($quote->subtotal, 2) }} €</span>
                    </div>
                    @if($quote->discount_amount > 0)
                    <div class="flex justify-between text-green-600">
                        <span>Remise globale</span>
                        <span>- {{ $quote->discount_type === 'percent' ? $quote->discount_amount . '%' : number_format($quote->discount_amount, 2) . ' €' }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between font-bold text-gray-900 border-t border-gray-100 pt-2 text-base">
                        <span>Total</span>
                        <span>{{ number_format($quote->total, 2) }} {{ $quote->currency }}</span>
                    </div>
                    @if($quote->deposit_percent > 0)
                    <div class="text-xs text-gray-500 space-y-1 pt-1 border-t border-gray-50">
                        <div class="flex justify-between">
                            <span>Acompte à la commande ({{ $quote->deposit_percent }}%)</span>
                            <span class="font-medium">{{ number_format($quote->depositAmount(), 2) }} €</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Solde à la livraison</span>
                            <span class="font-medium">{{ number_format($quote->balanceAmount(), 2) }} €</span>
                        </div>
                    </div>
                    @endif
                    <p class="text-xs text-gray-400">TVA non applicable, art. 293 B du CGI</p>
                </div>
            </div>
        </div>

        @if($quote->notes)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-2 text-sm">Remarques</h3>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $quote->notes }}</p>
        </div>
        @endif
    </div>

    {{-- Actions --}}
    <div class="space-y-5">

        @if($quote->isPending() && ! $quote->isExpired())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-3 text-sm">Votre réponse</h3>
            <p class="text-sm text-gray-500 mb-4">En validant ce devis, vous acceptez les termes et conditions de la prestation.</p>

            <div class="space-y-3">
                <form method="POST" action="{{ route('client.quotes.accept', $quote) }}"
                    onsubmit="return confirm('Accepter ce devis ? Une facture sera automatiquement générée.')">
                    @csrf
                    <button type="submit"
                        class="w-full px-4 py-3 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition">
                        Accepter le devis
                    </button>
                </form>

                <form method="POST" action="{{ route('client.quotes.refuse', $quote) }}"
                    onsubmit="return confirm('Refuser ce devis ?')">
                    @csrf
                    <button type="submit"
                        class="w-full px-4 py-3 bg-white border border-red-200 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition">
                        Refuser
                    </button>
                </form>
            </div>
        </div>
        @endif

        @if($quote->status === 'accepted')
        <div class="bg-green-50 border border-green-200 rounded-xl p-5">
            <div class="text-green-800 font-semibold text-sm mb-1">Devis accepté</div>
            <p class="text-green-700 text-xs">Merci ! Une facture a été générée. Vous pouvez la retrouver dans la section <a href="{{ route('client.billing.index') }}" class="underline">Facturation</a>.</p>
        </div>
        @endif

        @if($quote->status === 'refused')
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
            <div class="text-gray-700 font-semibold text-sm mb-1">Devis refusé</div>
            <p class="text-gray-500 text-xs">N'hésitez pas à nous contacter si vous souhaitez une nouvelle proposition.</p>
        </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 text-sm text-gray-600">
            <div class="space-y-1">
                <div class="flex justify-between">
                    <span class="text-gray-400">Référence</span>
                    <span class="font-mono font-medium">{{ $quote->number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Créé le</span>
                    <span>{{ $quote->created_at->format('d/m/Y') }}</span>
                </div>
                @if($quote->expires_at)
                <div class="flex justify-between">
                    <span class="text-gray-400">Valide jusqu'au</span>
                    <span>{{ $quote->expires_at->format('d/m/Y') }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
