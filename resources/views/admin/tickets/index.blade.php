@extends('layouts.app')
@section('title', 'Tickets')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Tickets support</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $tickets->total() }} ticket(s)</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-3 border-b border-gray-50 flex gap-3">
        <form method="GET" class="flex gap-2 flex-wrap">
            <select name="status" onchange="this.form.submit()"
                class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">Tous les statuts</option>
                <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Ouvert</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>En cours</option>
                <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Résolu</option>
                <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Fermé</option>
            </select>
            <select name="priority" onchange="this.form.submit()"
                class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">Toutes priorités</option>
                <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgente</option>
                <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>Haute</option>
                <option value="normal" {{ request('priority') === 'normal' ? 'selected' : '' }}>Normale</option>
                <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Basse</option>
            </select>
        </form>
    </div>

    <div class="divide-y divide-gray-50">
        @forelse($tickets as $ticket)
        <a href="{{ route('admin.tickets.show', $ticket) }}"
           class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="text-xs font-mono text-gray-400">#{{ $ticket->number }}</span>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $ticket->priority === 'urgent' ? 'bg-red-100 text-red-700' :
                           ($ticket->priority === 'high' ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-500') }}">
                        {{ ucfirst($ticket->priority) }}
                    </span>
                    @if($ticket->category)
                    <span class="text-xs text-gray-400">{{ $ticket->category }}</span>
                    @endif
                </div>
                <div class="font-medium text-sm text-gray-800 truncate">{{ $ticket->subject }}</div>
                <div class="text-xs text-gray-400 mt-0.5">{{ $ticket->user->full_name }} · {{ $ticket->created_at->diffForHumans() }}</div>
            </div>
            <div class="ml-4 shrink-0">
                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium
                    {{ $ticket->status === 'open' ? 'bg-blue-100 text-blue-700' :
                       ($ticket->status === 'in_progress' ? 'bg-amber-100 text-amber-700' :
                       ($ticket->status === 'resolved' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500')) }}">
                    {{ match($ticket->status) { 'open'=>'Ouvert','in_progress'=>'En cours','resolved'=>'Résolu','closed'=>'Fermé',default=>$ticket->status } }}
                </span>
            </div>
        </a>
        @empty
        <div class="px-5 py-12 text-center text-gray-400">Aucun ticket</div>
        @endforelse
    </div>
    @if($tickets->hasPages())
    <div class="px-5 py-4 border-t border-gray-50">{{ $tickets->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
