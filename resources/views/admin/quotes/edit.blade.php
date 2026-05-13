@extends('layouts.app')
@section('title', 'Modifier ' . $quote->number)
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.quotes.show', $quote) }}" class="text-sm text-gray-400 hover:text-gray-600">← {{ $quote->number }}</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Modifier le devis {{ $quote->number }}</h1>
</div>

<form method="POST" action="{{ route('admin.quotes.update', $quote) }}" class="space-y-5">
    @csrf @method('PUT')
    @include('admin.quotes._form')
    <div class="flex items-center gap-3">
        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            Enregistrer
        </button>
        <a href="{{ route('admin.quotes.show', $quote) }}" class="text-sm text-gray-500 hover:underline">Annuler</a>
    </div>
</form>
@endsection
