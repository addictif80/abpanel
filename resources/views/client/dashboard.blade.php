@extends('layouts.app')
@section('title', 'Mon espace')
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Bonjour, {{ auth()->user()->first_name ?: auth()->user()->name }} 👋</h1>
    <p class="text-gray-500 text-sm mt-1">Bienvenue sur votre espace client</p>
</div>

{{-- Alertes --}}
@if($pendingQuotes->count() > 0)
<div class="mb-3 flex items-center gap-3 bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    <span>Vous avez <strong>{{ $pendingQuotes->count() }} devis</strong> en attente de réponse.</span>
    <a href="{{ route('client.quotes.index') }}" class="ml-auto font-semibold hover:underline">Voir →</a>
</div>
@endif
@if($unpaidInvoices > 0)
<div class="mb-6 flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span>Vous avez <strong>{{ $unpaidInvoices }} facture(s) en attente</strong> de paiement.</span>
    <a href="{{ route('client.billing.index') }}" class="ml-auto font-semibold hover:underline">Voir →</a>
</div>
@endif

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="text-2xl font-bold text-indigo-600">{{ $vms->count() }}</div>
        <div class="text-xs text-gray-500 mt-1">Machine(s) virtuelle(s)</div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="text-2xl font-bold text-blue-600">{{ $hostingAccounts->count() }}</div>
        <div class="text-xs text-gray-500 mt-1">Hébergement(s)</div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="text-2xl font-bold text-amber-600">{{ $unpaidInvoices }}</div>
        <div class="text-xs text-gray-500 mt-1">Facture(s) impayée(s)</div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="text-2xl font-bold text-red-500">{{ $openTickets }}</div>
        <div class="text-xs text-gray-500 mt-1">Ticket(s) ouvert(s)</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

    {{-- Machines virtuelles --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
            <h2 class="font-semibold text-gray-800 text-sm">Machines virtuelles</h2>
            <a href="{{ route('client.vms.index') }}" class="text-xs text-indigo-600 hover:underline">Gérer →</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($vms->take(5) as $vm)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <div class="font-medium text-sm text-gray-800">{{ $vm->name }}</div>
                    <div class="text-xs text-gray-400">{{ $vm->cores }} vCPU · {{ $vm->memory_mb }}MB · {{ $vm->disk_gb }}GB</div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $vm->status === 'running' ? 'bg-green-100 text-green-700' :
                           ($vm->status === 'hibernated' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500') }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $vm->status === 'running' ? 'bg-green-500' : ($vm->status === 'hibernated' ? 'bg-amber-500' : 'bg-gray-400') }}"></span>
                        {{ ucfirst($vm->status) }}
                    </span>
                    <a href="{{ route('client.vms.show', $vm) }}" class="text-xs text-indigo-600 hover:underline">Gérer</a>
                </div>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-sm text-gray-400">
                <p class="mb-2">Aucune machine virtuelle</p>
                <a href="{{ route('client.checkout.plans') }}" class="text-indigo-600 hover:underline font-medium">Voir nos offres →</a>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Hébergements web --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
            <h2 class="font-semibold text-gray-800 text-sm">Hébergements web</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($hostingAccounts as $hosting)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <div class="font-medium text-sm text-gray-800">{{ $hosting->domain }}</div>
                    <div class="text-xs text-gray-400">{{ $hosting->plan }} · {{ $hosting->disk_mb }}MB</div>
                </div>
                @if(auth()->user()->cyberpanel_username)
                <a href="{{ \App\Models\Setting::get('cyberpanel_host') }}" target="_blank"
                   class="text-xs text-indigo-600 hover:underline">CyberPanel →</a>
                @endif
            </div>
            @empty
            <div class="px-5 py-8 text-center text-sm text-gray-400">
                <p class="mb-2">Aucun hébergement</p>
                <a href="{{ route('client.checkout.plans') }}" class="text-indigo-600 hover:underline font-medium">Voir nos offres →</a>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Devis en attente --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
            <h2 class="font-semibold text-gray-800 text-sm">Devis</h2>
            <a href="{{ route('client.quotes.index') }}" class="text-xs text-indigo-600 hover:underline">Tous les devis →</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($pendingQuotes as $quote)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <a href="{{ route('client.quotes.show', $quote) }}" class="font-medium text-sm text-indigo-600 hover:underline">{{ $quote->number }}</a>
                    <div class="text-xs text-gray-400">{{ $quote->created_at->format('d/m/Y') }}@if($quote->expires_at) · Expire le {{ $quote->expires_at->format('d/m/Y') }}@endif</div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="font-semibold text-sm text-gray-800">{{ number_format($quote->total, 2) }} {{ $quote->currency }}</span>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $quote->status === 'viewed' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                        {{ $quote->status === 'viewed' ? 'Consulté' : 'En attente' }}
                    </span>
                </div>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-sm text-gray-400">Aucun devis en attente</div>
            @endforelse
        </div>
    </div>

    {{-- Tickets ouverts --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
            <h2 class="font-semibold text-gray-800 text-sm">Tickets ouverts</h2>
            <a href="{{ route('client.tickets.index') }}" class="text-xs text-indigo-600 hover:underline">Tous les tickets →</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($openTicketsList as $ticket)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <a href="{{ route('client.tickets.show', $ticket) }}" class="font-medium text-sm text-indigo-600 hover:underline">#{{ $ticket->number }} — {{ $ticket->subject }}</a>
                    <div class="text-xs text-gray-400">{{ $ticket->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $ticket->status === 'in_progress' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                    {{ $ticket->status === 'in_progress' ? 'En cours' : 'Ouvert' }}
                </span>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-sm text-gray-400">Aucun ticket ouvert</div>
            @endforelse
        </div>
    </div>

    {{-- Factures & paiements --}}
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
            <h2 class="font-semibold text-gray-800 text-sm">Factures &amp; paiements</h2>
            <a href="{{ route('client.billing.index') }}" class="text-xs text-indigo-600 hover:underline">Toutes les factures →</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentInvoices as $invoice)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <a href="{{ route('client.billing.invoice', $invoice) }}" class="font-medium text-sm text-indigo-600 hover:underline">{{ $invoice->number }}</a>
                    <div class="text-xs text-gray-400">{{ $invoice->created_at->format('d/m/Y') }}@if($invoice->due_at) · Échéance {{ $invoice->due_at->format('d/m/Y') }}@endif</div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="font-semibold text-sm text-gray-800">{{ number_format($invoice->total, 2) }} {{ $invoice->currency ?? 'EUR' }}</span>
                    @if($invoice->isPaid())
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Payée</span>
                    @else
                    <a href="{{ route('client.billing.invoice.pay', $invoice) }}"
                       class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 hover:bg-amber-200 transition">
                        Payer →
                    </a>
                    @endif
                </div>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-sm text-gray-400">Aucune facture</div>
            @endforelse
        </div>
    </div>

</div>
@endsection
