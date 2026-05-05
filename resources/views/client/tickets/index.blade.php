@extends('layouts.app')
@section('title', 'Mes tickets')
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Support</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $tickets->total() }} ticket(s)</p>
    </div>
    <a href="{{ route('client.tickets.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        + Nouveau ticket
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="divide-y divide-gray-50">
        @forelse($tickets as $ticket)
        <a href="{{ route('client.tickets.show', $ticket) }}"
           class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-mono text-gray-400">#{{ $ticket->number }}</span>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $ticket->priority === 'urgent' ? 'bg-red-100 text-red-700' :
                           ($ticket->priority === 'high' ? 'bg-orange-100 text-orange-700' :
                           ($ticket->priority === 'normal' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500')) }}">
                        {{ ucfirst($ticket->priority) }}
                    </span>
                </div>
                <div class="font-medium text-gray-800 truncate">{{ $ticket->subject }}</div>
                <div class="text-xs text-gray-400 mt-0.5">{{ $ticket->created_at->diffForHumans() }}</div>
            </div>
            <div class="ml-4 shrink-0">
                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium
                    {{ $ticket->status === 'open' ? 'bg-blue-100 text-blue-700' :
                       ($ticket->status === 'in_progress' ? 'bg-amber-100 text-amber-700' :
                       ($ticket->status === 'resolved' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500')) }}">
                    {{ match($ticket->status) {
                        'open' => 'Ouvert',
                        'in_progress' => 'En cours',
                        'resolved' => 'Résolu',
                        'closed' => 'Fermé',
                        default => $ticket->status
                    } }}
                </span>
            </div>
        </a>
        @empty
        <div class="px-5 py-12 text-center">
            <p class="text-gray-400 text-sm mb-3">Aucun ticket ouvert.</p>
            <a href="{{ route('client.tickets.create') }}" class="inline-block px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                Créer un ticket
            </a>
        </div>
        @endforelse
    </div>
    @if($tickets->hasPages())
    <div class="px-5 py-4 border-t border-gray-50">{{ $tickets->links() }}</div>
    @endif
</div>
@endsection
