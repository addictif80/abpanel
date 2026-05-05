@extends('layouts.guest')
@section('title', 'Réinitialisation du mot de passe')

@section('content')
<div class="text-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Nouveau mot de passe</h1>
    <p class="text-gray-500 text-sm mt-1">Choisissez votre nouveau mot de passe.</p>
</div>

@if($errors->any())
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Adresse e-mail</label>
        <input type="email" name="email" value="{{ old('email', $email) }}" required
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
        <input type="password" name="password" required minlength="8"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
        <input type="password" name="password_confirmation" required
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
    </div>
    <button type="submit"
        class="w-full py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        Réinitialiser le mot de passe
    </button>
</form>
@endsection
