@extends('layouts.app')
@section('title', $quote->number)
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-start justify-between mb-6">
    <div>
        <a href="{{ route('admin.quotes.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Devis</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $quote->number }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $quote->subject ?: 'Sans objet' }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.quotes.download-pdf', $quote) }}"
           class="px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
            PDF
        </a>
        @if($quote->isEditable())
        <a href="{{ route('admin.quotes.edit', $quote) }}"
           class="px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
            Modifier
        </a>
        @endif
        @if(in_array($quote->status, ['draft', 'sent']))
        <form method="POST" action="{{ route('admin.quotes.send', $quote) }}">
            @csrf
            <button class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                {{ $quote->sent_at ? 'Renvoyer' : 'Envoyer au client' }}
            </button>
        </form>
        @endif
    </div>
</div>

@foreach(['success', 'error', 'info'] as $type)
@if(session($type))
@php $colors = ['success' => 'bg-green-50 border-green-200 text-green-700', 'error' => 'bg-red-50 border-red-200 text-red-700', 'info' => 'bg-blue-50 border-blue-200 text-blue-700']; @endphp
<div class="mb-4 px-4 py-3 {{ $colors[$type] }} border rounded-lg text-sm">{{ session($type) }}</div>
@endif
@endforeach

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- Main --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Status & tracking --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $quote->statusColor() }}">
                    {{ $quote->statusLabel() }}
                </span>
                <div class="text-xs text-gray-400 space-x-4">
                    @if($quote->sent_at)<span>Envoyé le {{ $quote->sent_at->format('d/m/Y H:i') }}</span>@endif
                    @if($quote->opened_at)<span>Consulté le {{ $quote->opened_at->format('d/m/Y H:i') }}</span>@endif
                    @if($quote->last_viewed_at && $quote->last_viewed_at->ne($quote->opened_at))<span>Dernière visite {{ $quote->last_viewed_at->format('d/m/Y H:i') }}</span>@endif
                </div>
            </div>
            @if($quote->expires_at)
            <p class="text-xs mt-2 {{ $quote->isExpired() ? 'text-red-500 font-medium' : 'text-gray-400' }}">
                Expire le {{ $quote->expires_at->format('d/m/Y') }}
                @if($quote->isExpired()) — <strong>EXPIRÉ</strong> @endif
            </p>
            @endif
            @if($quote->access_token && $quote->status !== 'draft')
            <p class="text-xs mt-2 text-gray-400">
                Lien client :
                <a href="{{ route('quotes.public', $quote->access_token) }}" target="_blank"
                   class="text-indigo-600 hover:underline break-all">
                    {{ route('quotes.public', $quote->access_token) }}
                </a>
            </p>
            @endif
        </div>

        {{-- Items --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Prestation</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Qté</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">P.U.</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Remise</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($quote->items as $item)
                    <tr>
                        <td class="px-5 py-3">
                            <div class="font-medium text-gray-800">{{ $item->description }}</div>
                            @if($item->details)<div class="text-xs text-gray-400">{{ $item->details }}</div>@endif
                        </td>
                        <td class="px-5 py-3 text-right text-gray-600">{{ $item->quantity }} {{ $item->unit }}</td>
                        <td class="px-5 py-3 text-right text-gray-600">{{ number_format($item->unit_price, 2) }} €</td>
                        <td class="px-5 py-3 text-right text-gray-500 text-xs">
                            @if($item->discount_amount > 0)
                                {{ $item->discount_type === 'percent' ? $item->discount_amount . '%' : number_format($item->discount_amount, 2) . ' €' }}
                            @else —
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-800">{{ number_format($item->total, 2) }} €</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="px-5 py-4 border-t border-gray-50 flex justify-end">
                <div class="w-64 space-y-1.5 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Sous-total HT</span>
                        <span>{{ number_format($quote->subtotal, 2) }} €</span>
                    </div>
                    @if($quote->discount_amount > 0)
                    <div class="flex justify-between text-gray-600">
                        <span>Remise globale</span>
                        <span class="text-red-500">
                            - {{ $quote->discount_type === 'percent' ? $quote->discount_amount . '%' : number_format($quote->discount_amount, 2) . ' €' }}
                        </span>
                    </div>
                    @endif
                    @if($quote->tax_amount > 0)
                    <div class="flex justify-between text-gray-600">
                        <span>TVA</span>
                        <span>{{ number_format($quote->tax_amount, 2) }} €</span>
                    </div>
                    @endif
                    <div class="flex justify-between font-bold text-gray-900 border-t border-gray-100 pt-2 text-base">
                        <span>Total</span>
                        <span>{{ number_format($quote->total, 2) }} {{ $quote->currency }}</span>
                    </div>
                    @if($quote->deposit_percent > 0)
                    <div class="flex justify-between text-indigo-600 text-xs pt-1">
                        <span>Acompte ({{ $quote->deposit_percent }}%)</span>
                        <span>{{ number_format($quote->depositAmount(), 2) }} €</span>
                    </div>
                    <div class="flex justify-between text-gray-500 text-xs">
                        <span>Solde</span>
                        <span>{{ number_format($quote->balanceAmount(), 2) }} €</span>
                    </div>
                    @endif
                    <p class="text-xs text-gray-400 pt-1">{{ \App\Models\Setting::get('vat_mention', 'TVA non applicable, art. 293 B du CGI') }}</p>
                </div>
            </div>
        </div>

        @if($quote->notes)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-2 text-sm">Notes client</h3>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $quote->notes }}</p>
        </div>
        @endif

        {{-- Linked invoices --}}
        @if($quote->invoices->count())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-3 text-sm">Factures associées</h3>
            <div class="space-y-2">
                @foreach($quote->invoices as $inv)
                <div class="flex items-center justify-between text-sm">
                    <a href="{{ route('admin.invoices.show', $inv) }}" class="font-mono text-indigo-600 hover:underline">{{ $inv->number }}</a>
                    <span class="text-gray-500">{{ $inv->typeLabel() }}</span>
                    <span class="font-semibold">{{ number_format($inv->total, 2) }} €</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $inv->isPaid() ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $inv->isPaid() ? 'Payée' : 'En attente' }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Sidebar --}}
    <div class="space-y-5">

        {{-- Client --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-3 text-sm">Client</h3>
            <div class="text-sm">
                <div class="font-medium text-gray-800">{{ $quote->user->full_name }}</div>
                <div class="text-gray-400">{{ $quote->user->email }}</div>
                @if($quote->user->company)<div class="text-gray-500">{{ $quote->user->company }}</div>@endif
                @if($quote->user->siret)<div class="text-xs text-gray-400 mt-1">SIRET : {{ $quote->user->siret }}</div>@endif
                @if($quote->user->isPro())
                <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">Client Pro</span>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-2">
            <h3 class="font-semibold text-gray-800 mb-3 text-sm">Actions</h3>

            @if($quote->isPending())
            <form method="POST" action="{{ route('admin.quotes.reminder', $quote) }}">
                @csrf
                <button class="w-full px-3 py-2 text-sm text-amber-700 bg-amber-50 border border-amber-100 rounded-lg hover:bg-amber-100 transition text-left">
                    Envoyer une relance
                </button>
            </form>
            @endif

            @if($quote->status === 'accepted' && $quote->invoices->isEmpty())
            <form method="POST" action="{{ route('admin.quotes.convert', $quote) }}">
                @csrf
                <button class="w-full px-3 py-2 text-sm text-green-700 bg-green-50 border border-green-100 rounded-lg hover:bg-green-100 transition text-left">
                    Convertir en facture
                </button>
            </form>
            @endif

            @if(! in_array($quote->status, ['invoiced', 'cancelled']))
            <div x-data="{ open: false }">
                <button type="button" @click="open = !open" class="w-full px-3 py-2 text-sm text-gray-600 bg-gray-50 border border-gray-100 rounded-lg hover:bg-gray-100 transition text-left">
                    Sauvegarder comme modèle
                </button>
                <div x-show="open" class="mt-2">
                    <form method="POST" action="{{ route('admin.quotes.save-template', $quote) }}" class="flex gap-2">
                        @csrf
                        <input type="text" name="template_name" placeholder="Nom du modèle" required
                            class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">OK</button>
                    </form>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.quotes.cancel', $quote) }}"
                onsubmit="return confirm('Annuler ce devis ?')">
                @csrf
                <button class="w-full px-3 py-2 text-sm text-red-600 bg-red-50 border border-red-100 rounded-lg hover:bg-red-100 transition text-left">
                    Annuler le devis
                </button>
            </form>
            @endif
        </div>

        {{-- Internal notes --}}
        @if($quote->internal_notes)
        <div class="bg-amber-50 border border-amber-100 rounded-xl p-4">
            <h3 class="font-semibold text-amber-800 mb-2 text-xs uppercase">Notes internes</h3>
            <p class="text-sm text-amber-700 whitespace-pre-line">{{ $quote->internal_notes }}</p>
        </div>
        @endif

        {{-- Client comment --}}
        @if($quote->client_comment)
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
            <h3 class="font-semibold text-blue-800 mb-2 text-xs uppercase">Message du client</h3>
            <p class="text-sm text-blue-700 whitespace-pre-line">{{ $quote->client_comment }}</p>
            @if($quote->cgv_accepted_at)
            <p class="text-xs text-blue-400 mt-2">CGV acceptées le {{ $quote->cgv_accepted_at->format('d/m/Y à H:i') }}</p>
            @endif
        </div>
        @endif

        {{-- Action log --}}
        @if($quote->logs->count())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-3 text-sm">Historique</h3>
            <ol class="space-y-2">
                @foreach($quote->logs as $log)
                <li class="flex items-start gap-2 text-xs text-gray-600">
                    <span class="shrink-0 w-5 text-center">{{ $log->actionIcon() }}</span>
                    <div>
                        <span class="font-medium text-gray-800">{{ $log->actionLabel() }}</span>
                        <span class="text-gray-400 ml-1">— {{ $log->created_at->format('d/m/Y H:i') }}</span>
                        <span class="text-gray-400 ml-1">({{ $log->actor }})</span>
                        @if($log->note)
                        <p class="text-gray-500 italic mt-0.5">{{ $log->note }}</p>
                        @endif
                    </div>
                </li>
                @endforeach
            </ol>
        </div>
        @endif
    </div>
</div>
@endsection
