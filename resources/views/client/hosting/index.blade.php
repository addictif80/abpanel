@extends('layouts.app')
@section('title', 'Mes sites')
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Mes sites</h1>
    <p class="text-gray-500 text-sm mt-1">{{ $hostingAccounts->count() }} compte(s) actif(s)</p>
</div>

@if($hostingAccounts->isEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
    <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
    </svg>
    <p class="text-gray-700 font-medium mb-1">Vous n'avez pas encore de site hébergé.</p>
    <p class="text-gray-400 text-sm mb-4">Consultez nos offres et souscrivez pour héberger vos sites.</p>
    <a href="{{ route('client.checkout.plans') }}" class="inline-block px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        Consulter les offres
    </a>
</div>
@else
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    @foreach($hostingAccounts as $hosting)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="font-semibold text-gray-900">{{ $hosting->domain }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $hosting->plan }}</p>
            </div>
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Actif</span>
        </div>

        <div class="grid grid-cols-2 gap-3 text-xs text-gray-500 mb-4">
            <div class="bg-gray-50 rounded-lg p-2 text-center">
                <div class="font-semibold text-gray-800 text-sm">{{ $hosting->disk_mb ? round($hosting->disk_mb / 1024, 1) . ' GB' : '—' }}</div>
                <div>Espace disque</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-2 text-center">
                <div class="font-semibold text-gray-800 text-sm">{{ $hosting->username ?: '—' }}</div>
                <div>Identifiant</div>
            </div>
        </div>

        @if($cyberpanelHost && auth()->user()->cyberpanel_username)
        <a href="{{ $cyberpanelHost }}" target="_blank"
           class="block w-full text-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            Accéder à CyberPanel →
        </a>
        @endif

        <div class="pt-3 mt-1 border-t border-gray-100">
            <a href="{{ route('client.hosting.cancel', $hosting) }}"
               class="flex items-center gap-2 px-2 py-1.5 text-xs text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Résilier cet hébergement
            </a>
        </div>
    </div>
    @endforeach
    <a href="{{ route('client.checkout.plans') }}"
       class="bg-white rounded-xl border-2 border-dashed border-indigo-300 p-5 flex flex-col items-center justify-center gap-3 hover:border-indigo-500 hover:bg-indigo-50 transition min-h-[160px]">
        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
            <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        </div>
        <span class="text-sm font-semibold text-indigo-600">Commander un nouvel hébergement</span>
    </a>
</div>
@endif
@endsection
