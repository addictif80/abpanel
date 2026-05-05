@extends('layouts.app')
@section('title', '#' . $ticket->number)
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6 flex items-start justify-between">
    <div>
        <a href="{{ route('admin.tickets.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Tickets</a>
        <h1 class="text-xl font-bold text-gray-900 mt-1">{{ $ticket->subject }}</h1>
        <div class="flex items-center gap-2 mt-1">
            <span class="text-xs text-gray-400 font-mono">#{{ $ticket->number }}</span>
            <span class="text-xs text-gray-400">·</span>
            <a href="{{ route('admin.clients.show', $ticket->user) }}" class="text-xs text-indigo-600 hover:underline">{{ $ticket->user->full_name }}</a>
        </div>
    </div>

    {{-- Status + priority controls --}}
    <form method="POST" action="{{ route('admin.tickets.status', $ticket) }}" class="flex items-center gap-2">
        @csrf
        <select name="status" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            @foreach(['open' => 'Ouvert', 'in_progress' => 'En cours', 'resolved' => 'Résolu', 'closed' => 'Fermé'] as $val => $label)
            <option value="{{ $val }}" {{ $ticket->status === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-3 py-1.5 bg-gray-800 text-white text-xs font-semibold rounded-lg hover:bg-gray-900 transition">
            Mettre à jour
        </button>
    </form>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    {{-- Messages --}}
    <div class="lg:col-span-3 space-y-4">
        @foreach($ticket->messages as $message)
        <div class="flex gap-3 {{ $message->is_admin ? 'flex-row-reverse' : '' }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-xs font-bold
                {{ $message->is_admin ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-200 text-gray-600' }}">
                {{ strtoupper(substr($message->user->first_name ?: $message->user->name, 0, 1)) }}
            </div>
            <div class="flex-1 max-w-lg">
                <div class="flex items-center gap-2 mb-1 {{ $message->is_admin ? 'flex-row-reverse' : '' }}">
                    <span class="text-xs font-semibold text-gray-700">
                        {{ $message->is_admin ? 'Support (vous)' : $message->user->full_name }}
                    </span>
                    <span class="text-xs text-gray-400">{{ $message->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="rounded-xl px-4 py-3 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed
                    {{ $message->is_admin ? 'bg-indigo-50 border border-indigo-100' : 'bg-white border border-gray-100 shadow-sm' }}">
                    {{ $message->message }}
                </div>
            </div>
        </div>
        @endforeach

        {{-- Reply form --}}
        @if(!in_array($ticket->status, ['closed']))
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mt-4">
            <h3 class="font-semibold text-gray-800 text-sm mb-3">Répondre au client</h3>
            <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}">
                @csrf
                <textarea name="message" rows="5" required placeholder="Votre réponse..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none mb-3"></textarea>
                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    Envoyer la réponse
                </button>
            </form>
        </div>
        @endif
    </div>

    {{-- Sidebar infos --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <h3 class="font-semibold text-gray-800 text-sm mb-3">Détails</h3>
            <dl class="space-y-2 text-sm">
                <div><dt class="text-xs text-gray-400 uppercase">Client</dt><dd class="text-gray-800 mt-0.5">{{ $ticket->user->full_name }}</dd></div>
                <div><dt class="text-xs text-gray-400 uppercase">Email</dt><dd class="text-gray-600 text-xs mt-0.5">{{ $ticket->user->email }}</dd></div>
                <div><dt class="text-xs text-gray-400 uppercase">Priorité</dt>
                    <dd class="mt-0.5">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $ticket->priority === 'urgent' ? 'bg-red-100 text-red-700' :
                               ($ticket->priority === 'high' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700') }}">
                            {{ ucfirst($ticket->priority) }}
                        </span>
                    </dd>
                </div>
                <div><dt class="text-xs text-gray-400 uppercase">Catégorie</dt><dd class="text-gray-800 mt-0.5">{{ $ticket->category ?: '—' }}</dd></div>
                <div><dt class="text-xs text-gray-400 uppercase">Créé le</dt><dd class="text-gray-600 text-xs mt-0.5">{{ $ticket->created_at->format('d/m/Y H:i') }}</dd></div>
                <div><dt class="text-xs text-gray-400 uppercase">Messages</dt><dd class="text-gray-800 mt-0.5">{{ $ticket->messages->count() }}</dd></div>
            </dl>
        </div>
    </div>
</div>
@endsection
