@extends('layouts.install')

@section('content')
<div class="text-center">
    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>
    <h2 class="text-2xl font-bold text-gray-900 mb-3">Installation terminée !</h2>
    <p class="text-gray-500 mb-8 max-w-md mx-auto">
        ABPanel est correctement installé et configuré. Vous pouvez maintenant vous connecter
        avec le compte administrateur que vous venez de créer.
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8 text-left">
        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
            <div class="text-gray-700 font-semibold text-sm mb-2">Prochaines étapes</div>
            <ul class="text-gray-500 text-xs space-y-1">
                <li>✓ Configurer les APIs (Proxmox, CyberPanel, NPM)</li>
                <li>✓ Configurer Stripe pour les paiements</li>
                <li>✓ Configurer le serveur SMTP</li>
                <li>✓ Personnaliser les templates mails</li>
            </ul>
        </div>
        <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-100">
            <div class="text-indigo-700 font-semibold text-sm mb-2">Accès rapide</div>
            <ul class="text-indigo-600 text-xs space-y-2">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:underline">→ Panel d'administration</a></li>
                <li><a href="{{ route('admin.settings.index') }}" class="hover:underline">→ Paramètres (APIs, SMTP...)</a></li>
            </ul>
        </div>
    </div>

    <a href="{{ route('login') }}"
       class="inline-flex items-center gap-2 px-8 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition">
        Se connecter
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
</div>
@endsection
