@extends('layouts.app')
@section('title', $client->full_name)
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('admin.clients.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Clients</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $client->full_name }}</h1>
        <p class="text-gray-500 text-sm">{{ $client->email }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.clients.edit', $client) }}"
           class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition">
            Modifier
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Colonne infos --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 text-sm mb-4">Informations</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Téléphone</dt><dd class="text-gray-800">{{ $client->phone ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Société</dt><dd class="text-gray-800">{{ $client->company ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Pays</dt><dd class="text-gray-800">{{ $client->country }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Statut</dt>
                    <dd><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $client->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $client->is_active ? 'Actif' : 'Désactivé' }}</span></dd>
                </div>
                <div class="flex justify-between"><dt class="text-gray-500">CyberPanel</dt><dd class="font-mono text-xs text-gray-800">{{ $client->cyberpanel_username ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Inscription</dt><dd class="text-gray-800">{{ $client->created_at->format('d/m/Y') }}</dd></div>
            </dl>
        </div>

        {{-- Compteurs --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 text-sm mb-3">Résumé</h2>
            <div class="grid grid-cols-2 gap-3 text-center">
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="text-xl font-bold text-indigo-600">{{ $client->virtualMachines->count() }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">VM(s)</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="text-xl font-bold text-blue-600">{{ $client->hostingAccounts->count() }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">Hébergement(s)</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="text-xl font-bold text-amber-600">{{ $client->invoices->where('status', 'pending')->count() }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">Facture(s) impayée(s)</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="text-xl font-bold text-red-500">{{ $client->tickets->whereIn('status', ['open', 'in_progress'])->count() }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">Ticket(s) ouvert(s)</div>
                </div>
            </div>
        </div>

        {{-- Reset password --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5" x-data="{ open: false }">
            <button @click="open = !open" class="text-sm font-semibold text-red-600 hover:text-red-700">
                Réinitialiser le mot de passe
            </button>
            <div x-show="open" x-transition class="mt-3">
                <form method="POST" action="{{ route('admin.clients.reset-password', $client) }}">
                    @csrf
                    <input type="password" name="password" placeholder="Nouveau mot de passe (min. 8 car.)"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-red-400 focus:outline-none mb-2">
                    <button type="submit"
                        class="w-full py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition">
                        Confirmer la réinitialisation
                    </button>
                    <p class="text-xs text-gray-400 mt-1">Le mot de passe sera synchronisé avec CyberPanel.</p>
                </form>
            </div>
        </div>
    </div>

    {{-- Colonne principale --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Machines virtuelles --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                <h2 class="font-semibold text-gray-800 text-sm">Machines virtuelles ({{ $client->virtualMachines->count() }})</h2>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($client->virtualMachines as $vm)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <div class="font-medium text-sm text-gray-800">{{ $vm->name }}</div>
                        <div class="text-xs text-gray-400">VMID {{ $vm->proxmox_vmid }} — {{ $vm->cores }} vCPU / {{ $vm->memory_mb }}MB RAM / {{ $vm->disk_gb }}GB</div>
                    </div>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $vm->status === 'running' ? 'bg-green-100 text-green-700' : ($vm->status === 'hibernated' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
                        {{ $vm->status }}
                    </span>
                </div>
                @empty
                <div class="px-5 py-6 text-center text-sm text-gray-400">Aucune VM</div>
                @endforelse
            </div>
        </div>

        {{-- Hébergements web --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                <h2 class="font-semibold text-gray-800 text-sm">Hébergements web ({{ $client->hostingAccounts->count() }})</h2>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($client->hostingAccounts as $hosting)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <div class="font-medium text-sm text-gray-800">{{ $hosting->domain }}</div>
                        <div class="text-xs text-gray-400">{{ $hosting->plan }} · {{ $hosting->disk_mb }}MB</div>
                    </div>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $hosting->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $hosting->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                </div>
                @empty
                <div class="px-5 py-6 text-center text-sm text-gray-400">Aucun hébergement</div>
                @endforelse
            </div>
        </div>

        {{-- Devis --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                <h2 class="font-semibold text-gray-800 text-sm">Devis récents</h2>
                <a href="{{ route('admin.quotes.index', ['search' => $client->email]) }}" class="text-xs text-indigo-600 hover:underline">Voir tout →</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($client->quotes as $quote)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <a href="{{ route('admin.quotes.show', $quote) }}" class="font-medium text-sm text-indigo-600 hover:underline">{{ $quote->number }}</a>
                        <div class="text-xs text-gray-400">{{ $quote->created_at->format('d/m/Y') }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-sm text-gray-800">{{ number_format($quote->total, 2) }} {{ $quote->currency }}</span>
                        @php
                            $qColor = match($quote->status) {
                                'draft'    => 'bg-gray-100 text-gray-600',
                                'sent'     => 'bg-blue-100 text-blue-700',
                                'viewed'   => 'bg-purple-100 text-purple-700',
                                'accepted' => 'bg-green-100 text-green-700',
                                'refused'  => 'bg-red-100 text-red-700',
                                'invoiced' => 'bg-indigo-100 text-indigo-700',
                                'expired'  => 'bg-orange-100 text-orange-700',
                                'cancelled'=> 'bg-gray-100 text-gray-500',
                                default    => 'bg-gray-100 text-gray-600',
                            };
                            $qLabel = match($quote->status) {
                                'draft'    => 'Brouillon',
                                'sent'     => 'Envoyé',
                                'viewed'   => 'Consulté',
                                'accepted' => 'Accepté',
                                'refused'  => 'Refusé',
                                'invoiced' => 'Facturé',
                                'expired'  => 'Expiré',
                                'cancelled'=> 'Annulé',
                                default    => $quote->status,
                            };
                        @endphp
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $qColor }}">{{ $qLabel }}</span>
                    </div>
                </div>
                @empty
                <div class="px-5 py-6 text-center text-sm text-gray-400">Aucun devis</div>
                @endforelse
            </div>
        </div>

        {{-- Factures & paiements --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                <h2 class="font-semibold text-gray-800 text-sm">Factures &amp; paiements</h2>
                <a href="{{ route('admin.invoices.index', ['search' => $client->email]) }}" class="text-xs text-indigo-600 hover:underline">Voir tout →</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($client->invoices as $invoice)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="font-medium text-sm text-indigo-600 hover:underline">{{ $invoice->number }}</a>
                        <div class="text-xs text-gray-400">{{ $invoice->created_at->format('d/m/Y') }}@if($invoice->due_at) · Échéance {{ $invoice->due_at->format('d/m/Y') }}@endif</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-sm text-gray-800">{{ number_format($invoice->total, 2) }} {{ $invoice->currency ?? 'EUR' }}</span>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $invoice->isPaid() ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $invoice->isPaid() ? 'Payée' : 'En attente' }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="px-5 py-6 text-center text-sm text-gray-400">Aucune facture</div>
                @endforelse
            </div>
        </div>

        {{-- Avoirs --}}
        @if($client->creditNotes->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                <h2 class="font-semibold text-gray-800 text-sm">Avoirs ({{ $client->creditNotes->count() }})</h2>
                <a href="{{ route('admin.credit-notes.index') }}" class="text-xs text-indigo-600 hover:underline">Voir tout →</a>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($client->creditNotes as $cn)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <a href="{{ route('admin.credit-notes.show', $cn) }}" class="font-medium text-sm text-indigo-600 hover:underline">{{ $cn->number }}</a>
                        <div class="text-xs text-gray-400">{{ $cn->created_at->format('d/m/Y') }} · Facture {{ $cn->invoice->number ?? '—' }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-sm text-gray-800">{{ number_format($cn->amount, 2) }} {{ $cn->currency }}</span>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $cn->status === 'applied' ? 'bg-green-100 text-green-700' : ($cn->status === 'issued' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                            {{ match($cn->status) { 'draft' => 'Brouillon', 'issued' => 'Émis', 'applied' => 'Appliqué', default => $cn->status } }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Tickets --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                <h2 class="font-semibold text-gray-800 text-sm">Tickets</h2>
                <a href="{{ route('admin.tickets.index') }}" class="text-xs text-indigo-600 hover:underline">Tous les tickets →</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($client->tickets as $ticket)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <a href="{{ route('admin.tickets.show', $ticket) }}" class="font-medium text-sm text-indigo-600 hover:underline">#{{ $ticket->number }} — {{ $ticket->subject }}</a>
                        <div class="text-xs text-gray-400">{{ $ticket->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $ticket->status === 'open' ? 'bg-amber-100 text-amber-700' : ($ticket->status === 'in_progress' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                        {{ match($ticket->status) { 'open' => 'Ouvert', 'in_progress' => 'En cours', 'closed' => 'Fermé', default => $ticket->status } }}
                    </span>
                </div>
                @empty
                <div class="px-5 py-6 text-center text-sm text-gray-400">Aucun ticket</div>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection
