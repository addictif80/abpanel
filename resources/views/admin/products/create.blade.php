@extends('layouts.app')
@section('title', 'Nouveau produit')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.products.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Catalogue produits</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Nouveau produit</h1>
</div>

<form method="POST" action="{{ route('admin.products.store') }}" class="max-w-2xl space-y-5">
    @csrf
    @include('admin.products._form')
    <div class="flex items-center gap-3">
        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            Créer le produit
        </button>
        <a href="{{ route('admin.products.index') }}" class="text-sm text-gray-500 hover:underline">Annuler</a>
    </div>
</form>
@endsection
