@extends('layouts.app')
@section('title', 'Modifier — ' . $plan->name)
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.plans.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Tarifs</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $plan->name }}</h1>
</div>

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="max-w-2xl space-y-5"
      x-data="{ planType: '{{ old('type', $plan->type) }}' }">
    @csrf @method('PUT')

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Informations générales</h2>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nom <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $plan->name) }}" required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                <select name="type" x-model="planType"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="vm" {{ old('type', $plan->type) === 'vm' ? 'selected' : '' }}>VPS / Serveur virtuel</option>
                    <option value="hosting" {{ old('type', $plan->type) === 'hosting' ? 'selected' : '' }}>Hébergement web</option>
                </select>
            </div>
            <div class="text-sm text-gray-500 flex items-center">Slug : <strong class="text-gray-700 font-mono ml-1">{{ $plan->slug }}</strong></div>
        </div>

        <div x-show="planType === 'vm'">
            <label class="block text-sm font-medium text-gray-700 mb-2">Type de virtualisation</label>
            <div class="grid grid-cols-2 gap-3">
                <label class="cursor-pointer">
                    <input type="radio" name="vm_type" value="qemu" class="sr-only peer" {{ old('vm_type', $plan->vm_type ?? 'qemu') === 'qemu' ? 'checked' : '' }}>
                    <div class="rounded-lg border-2 p-3 transition peer-checked:border-indigo-500 peer-checked:bg-indigo-50 border-gray-200 hover:border-gray-300">
                        <div class="font-semibold text-sm text-gray-800">Machine virtuelle (KVM)</div>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="vm_type" value="lxc" class="sr-only peer" {{ old('vm_type', $plan->vm_type) === 'lxc' ? 'checked' : '' }}>
                    <div class="rounded-lg border-2 p-3 transition peer-checked:border-indigo-500 peer-checked:bg-indigo-50 border-gray-200 hover:border-gray-300">
                        <div class="font-semibold text-sm text-gray-800">Conteneur (LXC)</div>
                    </div>
                </label>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="2"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('description', $plan->description) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fonctionnalités <span class="text-xs text-gray-400">(une par ligne)</span></label>
            <textarea name="features" rows="4"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">{{ old('features', is_array($plan->features) ? implode("\n", $plan->features) : '') }}</textarea>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Tarification</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prix (€) <span class="text-red-500">*</span></label>
                <input type="number" name="price" value="{{ old('price', $plan->price) }}" min="0" step="0.01" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div class="text-sm text-gray-500 flex items-center">
                Période : <strong class="text-gray-700 ml-1">{{ $plan->billing_period === 'yearly' ? 'Annuel' : 'Mensuel' }}</strong>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Stripe Price ID</label>
            <input type="text" name="stripe_price_id" value="{{ old('stripe_price_id', $plan->stripe_price_id) }}"
                placeholder="price_xxxxxxxxxxxx"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <p class="text-xs text-gray-400 mt-1">Laissez vide pour conserver la valeur actuelle.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Ressources</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">vCPU</label>
                <input type="number" name="cores" value="{{ old('cores', $plan->cores) }}" min="1"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">RAM (MB)</label>
                <input type="number" name="memory_mb" value="{{ old('memory_mb', $plan->memory_mb) }}" min="128"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Disque (GB)</label>
                <input type="number" name="disk_gb" value="{{ old('disk_gb', $plan->disk_gb) }}" min="1"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Options</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order) }}" min="0"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}
                        class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm font-medium text-gray-700">Plan actif</span>
                </label>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            Enregistrer
        </button>
        <a href="{{ route('admin.plans.index') }}" class="text-sm text-gray-500 hover:underline">Annuler</a>

        <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" class="ml-auto"
              onsubmit="return confirm('Supprimer définitivement ce plan ?')">
            @csrf @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 text-sm font-semibold rounded-lg hover:bg-red-100 transition">
                Supprimer
            </button>
        </form>
    </div>
</form>
@endsection
