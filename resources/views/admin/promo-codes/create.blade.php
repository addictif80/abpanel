@extends('layouts.app')
@section('title', 'Créer un code promo')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.promo-codes.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Codes promo</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Créer un code promo</h1>
</div>

@if($errors->any())
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('admin.promo-codes.store') }}" class="max-w-2xl space-y-5"
      x-data="{ type: '{{ old('discount_type', 'percent') }}' }">
    @csrf

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Identifiant</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Code <span class="text-red-500">*</span></label>
                <input type="text" name="code" value="{{ old('code') }}"
                    placeholder="EX: SUMMER20"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono uppercase focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    oninput="this.value=this.value.toUpperCase()" required>
                <p class="text-xs text-gray-400 mt-1">Automatiquement converti en majuscules.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <input type="text" name="description" value="{{ old('description') }}"
                    placeholder="Ex: Promo été 2026"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Remise</h2>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type de remise <span class="text-red-500">*</span></label>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer text-sm">
                    <input type="radio" name="discount_type" value="percent" x-model="type"
                        class="text-indigo-600 focus:ring-indigo-500">
                    Pourcentage (%)
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-sm">
                    <input type="radio" name="discount_type" value="fixed" x-model="type"
                        class="text-indigo-600 focus:ring-indigo-500">
                    Montant fixe (€)
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Valeur <span class="text-red-500">*</span>
                    <span class="text-gray-400 font-normal" x-text="type === 'percent' ? '(entre 0 et 100%)' : '(montant en €)'"></span>
                </label>
                <div class="relative">
                    <input type="number" name="discount_value" value="{{ old('discount_value') }}"
                        :max="type === 'percent' ? 100 : 9999"
                        min="0.01" step="0.01" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-10 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <span class="absolute right-3 top-2 text-gray-400 text-sm font-medium" x-text="type === 'percent' ? '%' : '€'"></span>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Montant minimum de commande</label>
                <div class="relative">
                    <input type="number" name="min_amount" value="{{ old('min_amount') }}"
                        min="0" step="0.01" placeholder="Optionnel"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-8 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <span class="absolute right-3 top-2 text-gray-400 text-sm">€</span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Limites</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre d'utilisations max</label>
                <input type="number" name="max_uses" value="{{ old('max_uses') }}"
                    min="1" placeholder="Illimité si vide"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date d'expiration</label>
                <input type="date" name="expires_at" value="{{ old('expires_at') }}"
                    min="{{ now()->addDay()->format('Y-m-d') }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <p class="text-xs text-gray-400 mt-1">Laisser vide pour un code sans expiration.</p>
            </div>
        </div>
    </div>

    @if($plans->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
        <div>
            <h2 class="font-semibold text-gray-800">Produits éligibles</h2>
            <p class="text-sm text-gray-500 mt-0.5">Laisser tout décoché pour un code valable sur tous les produits.</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            @foreach($plans as $plan)
            <label class="flex items-center gap-2 text-sm cursor-pointer p-2 rounded-lg hover:bg-gray-50">
                <input type="checkbox" name="plan_ids[]" value="{{ $plan->id }}"
                    {{ in_array($plan->id, old('plan_ids', [])) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-gray-700">{{ $plan->name }}</span>
                <span class="text-xs text-gray-400">{{ number_format($plan->price, 2) }}€</span>
            </label>
            @endforeach
        </div>
    </div>
    @endif

    <div class="flex items-center gap-3">
        <button type="submit"
            class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            Créer le code
        </button>
        <a href="{{ route('admin.promo-codes.index') }}" class="text-sm text-gray-500 hover:underline">Annuler</a>
    </div>
</form>
@endsection
