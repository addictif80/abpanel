@extends('layouts.app')
@section('title', 'Modifier ' . $product->name)
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.products.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Catalogue produits</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Modifier : {{ $product->name }}</h1>
</div>

<form method="POST" action="{{ route('admin.products.update', $product) }}" class="max-w-2xl space-y-5">
    @csrf @method('PUT')
    @include('admin.products._form')
    <div class="flex items-center gap-3">
        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            Enregistrer
        </button>
        <a href="{{ route('admin.products.index') }}" class="text-sm text-gray-500 hover:underline">Annuler</a>
    </div>
</form>
@endsection
