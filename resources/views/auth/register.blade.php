@extends('layouts.guest')

@section('title', 'Créer un compte')
@section('heading', 'Créer votre compte')

@section('content')
<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">Prénom</label>
            <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" required autofocus
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('first_name') border-red-400 @enderror">
            @error('first_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
            <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('last_name') border-red-400 @enderror">
            @error('last_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('email') border-red-400 @enderror">
        @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
        <input id="password" type="password" name="password" required
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('password') border-red-400 @enderror">
        @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>

    <label class="flex items-start gap-2 text-sm text-gray-600 cursor-pointer">
        <input type="checkbox" name="newsletter" value="1" class="mt-0.5 rounded border-gray-300 text-indigo-600">
        <span>Je souhaite recevoir les actualités et offres par email</span>
    </label>

    <button type="submit"
        class="w-full py-2.5 px-4 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition text-sm">
        Créer mon compte
    </button>
</form>

<p class="mt-6 text-center text-sm text-gray-500">
    Déjà un compte ?
    <a href="{{ route('login') }}" class="text-indigo-600 hover:underline font-medium">Se connecter</a>
</p>
@endsection
