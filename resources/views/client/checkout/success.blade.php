@extends('layouts.app')
@section('title', 'Commande confirmée')
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="max-w-md mx-auto text-center py-16">
    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
        <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Commande confirmée !</h1>
    <p class="text-gray-500 mb-6">Votre paiement a été traité avec succès. Vous recevrez une confirmation par e-mail.</p>
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <a href="{{ route('client.billing.index') }}"
           class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            Voir mes factures
        </a>
        <a href="{{ route('client.dashboard') }}"
           class="px-5 py-2.5 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition">
            Tableau de bord
        </a>
    </div>
</div>
@endsection
