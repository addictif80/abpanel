@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('sidebar')
    <x-admin-sidebar />
@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
    <p class="text-gray-500 text-sm mt-1">Vue d'ensemble de votre infrastructure</p>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 col-span-1">
        <div class="text-2xl font-bold text-indigo-600">{{ $stats['clients'] }}</div>
        <div class="text-xs text-gray-500 mt-1">Clients</div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="text-2xl font-bold text-blue-600">{{ $stats['vms'] }}</div>
        <div class="text-xs text-gray-500 mt-1">VMs totales</div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="text-2xl font-bold text-green-600">{{ $stats['vms_running'] }}</div>
        <div class="text-xs text-gray-500 mt-1">VMs actives</div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="text-2xl font-bold text-amber-600">{{ $stats['open_tickets'] }}</div>
        <div class="text-xs text-gray-500 mt-1">Tickets ouverts</div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="text-2xl font-bold text-emerald-600">{{ number_format($stats['revenue_month'], 2) }}€</div>
        <div class="text-xs text-gray-500 mt-1">CA ce mois</div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="text-2xl font-bold text-emerald-800">{{ number_format($stats['revenue_total'], 2) }}€</div>
        <div class="text-xs text-gray-500 mt-1">CA total</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Tickets ouverts --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
            <h2 class="font-semibold text-gray-800 text-sm">Tickets en attente</h2>
            <a href="{{ route('admin.tickets.index') }}" class="text-xs text-indigo-600 hover:underline">Voir tout</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentTickets as $ticket)
            <div class="px-5 py-3">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <a href="{{ route('admin.tickets.show', $ticket) }}" class="text-sm font-medium text-gray-800 hover:text-indigo-600 truncate block">
                            #{{ $ticket->number }} — {{ $ticket->subject }}
                        </a>
                        <span class="text-xs text-gray-400">{{ $ticket->user->full_name }}</span>
                    </div>
                    <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $ticket->priority === 'urgent' ? 'bg-red-100 text-red-700' : ($ticket->priority === 'high' ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-600') }}">
                        {{ $ticket->priority }}
                    </span>
                </div>
            </div>
            @empty
            <div class="px-5 py-6 text-center text-sm text-gray-400">Aucun ticket en attente</div>
            @endforelse
        </div>
    </div>

    {{-- Nouveaux clients --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
            <h2 class="font-semibold text-gray-800 text-sm">Nouveaux clients</h2>
            <a href="{{ route('admin.clients.index') }}" class="text-xs text-indigo-600 hover:underline">Voir tout</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentClients as $client)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-800">{{ $client->full_name }}</div>
                    <div class="text-xs text-gray-400">{{ $client->email }}</div>
                </div>
                <div class="text-xs text-gray-400">{{ $client->created_at->diffForHumans() }}</div>
            </div>
            @empty
            <div class="px-5 py-6 text-center text-sm text-gray-400">Aucun client</div>
            @endforelse
        </div>
    </div>

    {{-- Dernières factures --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
            <h2 class="font-semibold text-gray-800 text-sm">Dernières factures</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentInvoices as $invoice)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-800">{{ $invoice->number }}</div>
                    <div class="text-xs text-gray-400">{{ $invoice->user->full_name }}</div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-semibold {{ $invoice->isPaid() ? 'text-green-600' : 'text-amber-600' }}">
                        {{ number_format($invoice->total, 2) }}€
                    </div>
                    <div class="text-xs text-gray-400">{{ $invoice->isPaid() ? 'Payée' : 'En attente' }}</div>
                </div>
            </div>
            @empty
            <div class="px-5 py-6 text-center text-sm text-gray-400">Aucune facture</div>
            @endforelse
        </div>
    </div>

</div>
@endsection
