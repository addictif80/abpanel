@extends('layouts.app')
@section('title', 'Nouveau devis')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.quotes.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Devis</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Nouveau devis</h1>
</div>

@if($templates->count())
<div class="mb-5 bg-indigo-50 border border-indigo-100 rounded-xl p-4 flex items-center gap-4">
    <svg class="w-5 h-5 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    <div class="flex-1">
        <p class="text-sm text-indigo-800 font-medium">Partir d'un modèle</p>
        <p class="text-xs text-indigo-600">Sélectionnez un modèle pour pré-remplir le devis.</p>
    </div>
    <select onchange="window.location = '{{ route('admin.quotes.create') }}?template=' + this.value" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
        <option value="">Choisir un modèle...</option>
        @foreach($templates as $t)
        <option value="{{ $t->id }}" {{ request('template') == $t->id ? 'selected' : '' }}>{{ $t->template_name }}</option>
        @endforeach
    </select>
</div>
@endif

<form method="POST" action="{{ route('admin.quotes.store') }}" class="space-y-5">
    @csrf
    @include('admin.quotes._form')
    <div class="flex items-center gap-3">
        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            Créer le devis
        </button>
        <a href="{{ route('admin.quotes.index') }}" class="text-sm text-gray-500 hover:underline">Annuler</a>
    </div>
</form>
@endsection
