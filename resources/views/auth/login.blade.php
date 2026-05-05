@extends('layouts.guest')

@section('title', 'Connexion')
@section('heading', 'Connexion à votre espace')

@section('content')
<form method="POST" action="{{ route('login') }}" class="space-y-5">
    @csrf

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('email') border-red-400 @enderror">
        @error('email')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
        <input id="password" type="password" name="password" required
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('password') border-red-400 @enderror">
        @error('password')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600">
            Se souvenir de moi
        </label>
    </div>

    <button type="submit"
        class="w-full py-2.5 px-4 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition text-sm">
        Se connecter
    </button>
</form>

@if(config('app.registration_open', true))
<p class="mt-6 text-center text-sm text-gray-500">
    Pas encore de compte ?
    <a href="{{ route('register') }}" class="text-indigo-600 hover:underline font-medium">Créer un compte</a>
</p>
@endif
@endsection
