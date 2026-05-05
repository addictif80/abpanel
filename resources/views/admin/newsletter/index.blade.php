@extends('layouts.app')
@section('title', 'Newsletter')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">Newsletter</h1>
    <a href="{{ route('admin.newsletter.campaigns.create') }}"
       class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        + Nouvelle campagne
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Listes --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800 text-sm">Listes d'abonnés</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($lists as $list)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <a href="{{ route('admin.newsletter.lists.show', $list) }}" class="font-medium text-sm text-gray-800 hover:text-indigo-600">
                        {{ $list->name }}
                        @if($list->is_default)<span class="ml-1 text-xs text-indigo-500">(défaut)</span>@endif
                    </a>
                    <div class="text-xs text-gray-400">{{ $list->active_subscribers_count }} actif(s) / {{ $list->subscribers_count }} total</div>
                </div>
                <form method="POST" action="{{ route('admin.newsletter.lists.destroy', $list) }}" onsubmit="return confirm('Supprimer cette liste ?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-400 hover:text-red-600">Suppr.</button>
                </form>
            </div>
            @endforeach
        </div>

        {{-- Créer liste --}}
        <div class="px-5 py-4 border-t border-gray-50" x-data="{ open: false }">
            <button @click="open = !open" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">+ Nouvelle liste</button>
            <div x-show="open" x-transition class="mt-3">
                <form method="POST" action="{{ route('admin.newsletter.lists.store') }}" class="space-y-2">
                    @csrf
                    <input type="text" name="name" placeholder="Nom de la liste" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="is_default" value="1" class="rounded border-gray-300 text-indigo-600">
                        Liste par défaut (inscription)
                    </label>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                        Créer
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Campagnes --}}
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-50">
            <h2 class="font-semibold text-gray-800 text-sm">Campagnes</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($campaigns as $campaign)
            <div class="px-5 py-4 flex items-center justify-between">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-medium text-sm text-gray-800">{{ $campaign->name }}</span>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $campaign->status === 'sent' ? 'bg-green-100 text-green-700' :
                               ($campaign->status === 'sending' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500') }}">
                            {{ match($campaign->status) { 'sent'=>'Envoyée','sending'=>'En cours','draft'=>'Brouillon', default=>$campaign->status } }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-400">
                        {{ $campaign->list->name }} ·
                        {{ $campaign->status === 'sent' ? $campaign->sent_count . ' envois · ' . $campaign->sent_at->format('d/m/Y') : 'Non envoyée' }}
                    </div>
                </div>
                <div class="flex items-center gap-3 ml-4 shrink-0">
                    @if($campaign->status !== 'sent')
                    <a href="{{ route('admin.newsletter.campaigns.edit', $campaign) }}" class="text-xs text-gray-500 hover:text-indigo-600">Modifier</a>
                    <form method="POST" action="{{ route('admin.newsletter.campaigns.send', $campaign) }}"
                        onsubmit="return confirm('Envoyer cette campagne à tous les abonnés actifs ?')">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition">
                            Envoyer
                        </button>
                    </form>
                    @else
                    <span class="text-xs text-gray-400">{{ $campaign->sent_count }} envois</span>
                    @endif
                </div>
            </div>
            @empty
            <div class="px-5 py-10 text-center text-gray-400 text-sm">
                Aucune campagne. <a href="{{ route('admin.newsletter.campaigns.create') }}" class="text-indigo-600 hover:underline">Créer la première →</a>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
