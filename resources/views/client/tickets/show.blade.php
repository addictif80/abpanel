@extends('layouts.app')
@section('title', '#' . $ticket->number . ' — ' . $ticket->subject)
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="flex items-start justify-between mb-6">
    <div>
        <a href="{{ route('client.tickets.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Support</a>
        <h1 class="text-xl font-bold text-gray-900 mt-1">{{ $ticket->subject }}</h1>
        <div class="flex items-center gap-2 mt-1">
            <span class="text-xs text-gray-400 font-mono">#{{ $ticket->number }}</span>
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                {{ $ticket->priority === 'urgent' ? 'bg-red-100 text-red-700' :
                   ($ticket->priority === 'high' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700') }}">
                {{ ucfirst($ticket->priority) }}
            </span>
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                {{ $ticket->status === 'open' ? 'bg-blue-100 text-blue-700' :
                   ($ticket->status === 'in_progress' ? 'bg-amber-100 text-amber-700' :
                   ($ticket->status === 'resolved' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500')) }}">
                {{ match($ticket->status) { 'open'=>'Ouvert','in_progress'=>'En cours','resolved'=>'Résolu','closed'=>'Fermé',default=>$ticket->status } }}
            </span>
        </div>
    </div>
</div>

<div class="max-w-3xl space-y-4 mb-6">
    @foreach($ticket->messages as $message)
    <div class="flex gap-3 {{ $message->is_admin ? '' : 'flex-row-reverse' }}">
        <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-xs font-bold
            {{ $message->is_admin ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-200 text-gray-600' }}">
            {{ $message->is_admin ? 'S' : strtoupper(substr($message->user->first_name ?: $message->user->name, 0, 1)) }}
        </div>
        <div class="flex-1 max-w-lg">
            <div class="flex items-center gap-2 mb-1 {{ $message->is_admin ? '' : 'flex-row-reverse' }}">
                <span class="text-xs font-semibold text-gray-700">
                    {{ $message->is_admin ? 'Support' : $message->user->full_name }}
                </span>
                <span class="text-xs text-gray-400">{{ $message->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="rounded-xl px-4 py-3 text-sm text-gray-700 leading-relaxed whitespace-pre-wrap
                {{ $message->is_admin ? 'bg-indigo-50 border border-indigo-100' : 'bg-white border border-gray-100 shadow-sm' }}">
                {{ $message->message }}
            </div>
        </div>
    </div>
    @endforeach
</div>

@if(!in_array($ticket->status, ['closed']))
<div class="max-w-3xl bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h2 class="font-semibold text-gray-800 text-sm mb-3">Répondre</h2>
    <form method="POST" action="{{ route('client.tickets.reply', $ticket) }}">
        @csrf
        <textarea name="message" rows="4" required placeholder="Votre réponse..."
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none mb-3">{{ old('message') }}</textarea>
        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            Envoyer
        </button>
    </form>
</div>
@else
<div class="max-w-3xl bg-gray-50 rounded-xl border border-gray-200 p-4 text-sm text-gray-500 text-center">
    Ce ticket est fermé. <a href="{{ route('client.tickets.create') }}" class="text-indigo-600 hover:underline">Ouvrir un nouveau ticket →</a>
</div>
@endif
@endsection
