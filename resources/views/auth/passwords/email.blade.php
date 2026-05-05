@extends('layouts.guest')
@section('title', 'Mot de passe oublié')

@section('content')
<div class="text-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Mot de passe oublié</h1>
    <p class="text-gray-500 text-sm mt-1">Recevez un lien de réinitialisation par e-mail.</p>
</div>

@if(session('status'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('status') }}</div>
@endif

@if($errors->any())
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Adresse e-mail</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
    </div>
    <button type="submit"
        class="w-full py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
        Envoyer le lien
    </button>
    <p class="text-center text-sm text-gray-400">
        <a href="{{ route('login') }}" class="hover:underline text-gray-500">Retour à la connexion</a>
    </p>
</form>
@endsection
