@extends('layouts.install')

@section('content')
<h2 class="text-xl font-bold text-gray-900 mb-1">Configuration Stripe</h2>
<p class="text-gray-500 text-sm mb-6">
    Stripe est utilisé pour les paiements en ligne. Ces clés se trouvent dans votre
    <a href="https://dashboard.stripe.com/apikeys" target="_blank" class="text-indigo-600 hover:underline">tableau de bord Stripe</a>.
</p>

<form method="POST" action="{{ route('install.stripe.save') }}">
    @csrf

    <div class="space-y-4 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Clé publique <span class="text-gray-400 font-normal">(pk_live_… ou pk_test_…)</span></label>
            <input type="text" name="stripe_key" value="{{ old('stripe_key') }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="pk_live_...">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Clé secrète <span class="text-gray-400 font-normal">(sk_live_… ou sk_test_…)</span></label>
            <input type="password" name="stripe_secret" value="{{ old('stripe_secret') }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="sk_live_...">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Secret webhook <span class="text-gray-400 font-normal">(whsec_…)</span></label>
            <input type="password" name="stripe_webhook_secret" value="{{ old('stripe_webhook_secret') }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="whsec_...">
            <p class="mt-1 text-xs text-gray-400">
                À créer dans Stripe → Developers → Webhooks. URL à enregistrer :
                <code class="font-mono bg-gray-100 px-1 rounded">{{ config('app.url') }}/webhook/stripe</code>
            </p>
        </div>
    </div>

    <div class="rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800 mb-6">
        Ces champs sont <strong>optionnels</strong> — vous pouvez les renseigner plus tard depuis
        <strong>Admin → Paramètres → Stripe</strong>. Les paiements ne fonctionneront pas tant qu'ils ne sont pas configurés.
    </div>

    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
        <a href="{{ route('install.mail') }}" class="text-sm text-gray-500 hover:text-gray-700">← Retour</a>
        <button type="submit"
            class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition text-sm">
            Continuer
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>
</form>
@endsection
