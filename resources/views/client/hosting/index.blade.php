@extends('layouts.app')
@section('title', 'Hébergement web')
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Hébergement web</h1>
    <p class="text-gray-500 text-sm mt-1">{{ $hostingAccounts->count() }} compte(s) actif(s)</p>
</div>

@if($hostingAccounts->isEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
    <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
    </svg>
    <p class="text-gray-700 font-medium mb-1">Vous n'avez pas encore d'hébergement web.</p>
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
    </div>
    @endforeach
</div>
@endif
@endsection
