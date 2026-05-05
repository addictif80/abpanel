@extends('layouts.install')

@section('content')
<h2 class="text-xl font-bold text-gray-900 mb-1">Configuration de la base de données</h2>
<p class="text-gray-500 text-sm mb-6">Renseignez vos informations MySQL et les paramètres généraux.</p>

<form method="POST" action="{{ route('install.database.save') }}" x-data="{ testing: false, testResult: null }">
    @csrf

    {{-- App settings --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Application</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom du panel</label>
                <input type="text" name="app_name" value="{{ old('app_name', 'ABPanel') }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('app_name') border-red-400 @enderror">
                @error('app_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">URL de l'application</label>
                <input type="url" name="app_url" value="{{ old('app_url', request()->getSchemeAndHttpHost()) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('app_url') border-red-400 @enderror">
                @error('app_url')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- DB settings --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Base de données MySQL</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Hôte</label>
                <input type="text" name="db_host" value="{{ old('db_host', '127.0.0.1') }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('db_host') border-red-400 @enderror">
                @error('db_host')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                <input type="number" name="db_port" value="{{ old('db_port', '3306') }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la base</label>
                <input type="text" name="db_name" value="{{ old('db_name') }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Utilisateur</label>
                <input type="text" name="db_username" value="{{ old('db_username') }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                <input type="password" name="db_password"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
        <a href="{{ route('install.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Retour</a>
        <button type="submit"
            class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition text-sm">
            Valider et continuer
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>
</form>
@endsection
