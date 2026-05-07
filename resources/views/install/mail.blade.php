@extends('layouts.install')

@section('content')
<h2 class="text-xl font-bold text-gray-900 mb-1">Configuration des emails</h2>
<p class="text-gray-500 text-sm mb-6">Ces paramètres seront utilisés pour envoyer les emails de bienvenue, factures et tickets.</p>

<form method="POST" action="{{ route('install.mail.save') }}"
      x-data="{
          mailer: 'smtp',
          testing: false,
          testResult: null,
          async testSmtp() {
              this.testing = true;
              this.testResult = null;
              try {
                  const form = document.getElementById('mail-form');
                  const data = new FormData(form);
                  const res = await fetch('{{ route('install.mail.test') }}', {
                      method: 'POST',
                      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                      body: data,
                  });
                  const json = await res.json();
                  this.testResult = json.success ? 'ok' : json.error;
              } catch(e) {
                  this.testResult = e.message;
              }
              this.testing = false;
          }
      }" id="mail-form">
    @csrf

    {{-- Expéditeur --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Expéditeur</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Adresse d'envoi</label>
                <input type="email" name="mail_from_address" value="{{ old('mail_from_address', 'noreply@'.parse_url(config('app.url'), PHP_URL_HOST)) }}" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('mail_from_address') border-red-400 @enderror">
                @error('mail_from_address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom d'affichage</label>
                <input type="text" name="mail_from_name" value="{{ old('mail_from_name', config('app.name')) }}" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('mail_from_name') border-red-400 @enderror">
                @error('mail_from_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- Driver --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Méthode d'envoi</h3>
        <div class="grid grid-cols-2 gap-3 mb-4">
            <label class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition"
                :class="mailer === 'smtp' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                <input type="radio" name="mail_mailer" value="smtp" x-model="mailer" class="sr-only">
                <div class="flex-1">
                    <div class="font-semibold text-sm text-gray-900">SMTP</div>
                    <div class="text-xs text-gray-500">Serveur email dédié (recommandé en production)</div>
                </div>
                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center"
                    :class="mailer === 'smtp' ? 'border-indigo-500' : 'border-gray-300'">
                    <div class="w-2 h-2 rounded-full bg-indigo-500" x-show="mailer === 'smtp'"></div>
                </div>
            </label>
            <label class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition"
                :class="mailer === 'log' ? 'border-amber-400 bg-amber-50' : 'border-gray-200 hover:border-gray-300'">
                <input type="radio" name="mail_mailer" value="log" x-model="mailer" class="sr-only">
                <div class="flex-1">
                    <div class="font-semibold text-sm text-gray-900">Log (développement)</div>
                    <div class="text-xs text-gray-500">Emails écrits dans les logs uniquement</div>
                </div>
                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center"
                    :class="mailer === 'log' ? 'border-amber-400' : 'border-gray-300'">
                    <div class="w-2 h-2 rounded-full bg-amber-400" x-show="mailer === 'log'"></div>
                </div>
            </label>
        </div>

        {{-- SMTP fields --}}
        <div x-show="mailer === 'smtp'" x-transition>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hôte SMTP</label>
                    <input type="text" name="mail_host" value="{{ old('mail_host', 'smtp.example.com') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('mail_host') border-red-400 @enderror"
                        placeholder="smtp.mailgun.org">
                    @error('mail_host')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                    <input type="number" name="mail_port" value="{{ old('mail_port', 587) }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="587">
                    <p class="text-xs text-gray-400 mt-1">587 (TLS) ou 465 (SSL)</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Identifiant SMTP</label>
                    <input type="text" name="mail_username" value="{{ old('mail_username') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe SMTP</label>
                    <input type="password" name="mail_password"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            {{-- SMTP test --}}
            <div class="mt-4 flex items-center gap-3">
                <button type="button" @click="testSmtp()" :disabled="testing"
                    class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition disabled:opacity-50">
                    <span x-text="testing ? 'Test en cours…' : 'Tester la connexion SMTP'"></span>
                </button>
                <span x-show="testResult === 'ok'" class="text-green-600 text-sm font-medium">✓ Connexion réussie — email de test envoyé</span>
                <span x-show="testResult && testResult !== 'ok'" class="text-red-600 text-sm" x-text="testResult"></span>
            </div>
        </div>

        <div x-show="mailer === 'log'" x-transition>
            <div class="mt-2 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                Les emails seront écrits dans <code class="font-mono">storage/logs/laravel.log</code>. À utiliser uniquement en développement.
            </div>
            <input type="hidden" name="mail_host" value="127.0.0.1">
            <input type="hidden" name="mail_port" value="2525">
        </div>
    </div>

    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
        <a href="{{ route('install.database') }}" class="text-sm text-gray-500 hover:text-gray-700">← Retour</a>
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
