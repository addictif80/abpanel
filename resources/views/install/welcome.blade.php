@extends('layouts.install')

@section('content')
<div class="text-center">
    <div class="w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
        </svg>
    </div>
    <h2 class="text-2xl font-bold text-gray-900 mb-3">Bienvenue dans l'assistant d'installation</h2>
    <p class="text-gray-500 mb-8 max-w-md mx-auto">
        Cet assistant va vous guider pour configurer ABPanel en quelques étapes simples.
        Assurez-vous d'avoir vos informations de base de données MySQL à portée de main.
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8 text-left">
        <div class="bg-indigo-50 rounded-xl p-4">
            <div class="text-indigo-600 font-bold text-sm mb-1">① Base de données</div>
            <p class="text-gray-500 text-xs">Connexion MySQL et configuration de l'application</p>
        </div>
        <div class="bg-indigo-50 rounded-xl p-4">
            <div class="text-indigo-600 font-bold text-sm mb-1">② Administrateur</div>
            <p class="text-gray-500 text-xs">Création du compte administrateur principal</p>
        </div>
        <div class="bg-indigo-50 rounded-xl p-4">
            <div class="text-indigo-600 font-bold text-sm mb-1">③ Terminé</div>
            <p class="text-gray-500 text-xs">Votre panel est prêt à l'emploi</p>
        </div>
    </div>

    <a href="{{ route('install.database') }}"
       class="inline-flex items-center gap-2 px-8 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition">
        Commencer l'installation
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
</div>
@endsection
