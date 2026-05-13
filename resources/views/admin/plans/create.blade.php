@extends('layouts.app')
@section('title', 'Nouveau plan')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.plans.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Tarifs</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Nouveau plan</h1>
</div>

@if($errors->any())
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('admin.plans.store') }}" class="max-w-2xl space-y-5"
      x-data="planForm('{{ old('type', 'vm') }}', '{{ old('cyberpanel_package') }}')">
    @csrf

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Informations générales</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie <span class="text-red-500">*</span></label>
                <select name="type" required x-model="planType"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="vm" {{ old('type', 'vm') === 'vm' ? 'selected' : '' }}>VPS / Serveur virtuel</option>
                    <option value="hosting" {{ old('type') === 'hosting' ? 'selected' : '' }}>Hébergement web</option>
                </select>
            </div>
        </div>

        <div x-show="planType === 'vm'" x-cloak>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type de virtualisation <span class="text-red-500">*</span></label>
            <div class="grid grid-cols-2 gap-3">
                <label class="cursor-pointer">
                    <input type="radio" name="vm_type" value="qemu" class="sr-only peer" {{ old('vm_type', 'qemu') === 'qemu' ? 'checked' : '' }}>
                    <div class="rounded-lg border-2 p-3 transition peer-checked:border-indigo-500 peer-checked:bg-indigo-50 border-gray-200 hover:border-gray-300">
                        <div class="font-semibold text-sm text-gray-800">Machine virtuelle</div>
                        <div class="text-xs text-gray-500 mt-0.5">KVM / QEMU — isolation complète</div>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="vm_type" value="lxc" class="sr-only peer" {{ old('vm_type') === 'lxc' ? 'checked' : '' }}>
                    <div class="rounded-lg border-2 p-3 transition peer-checked:border-indigo-500 peer-checked:bg-indigo-50 border-gray-200 hover:border-gray-300">
                        <div class="font-semibold text-sm text-gray-800">Conteneur</div>
                        <div class="text-xs text-gray-500 mt-0.5">LXC — léger, démarrage rapide</div>
                    </div>
                </label>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="2"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('description') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fonctionnalités <span class="text-xs text-gray-400">(une par ligne)</span></label>
            <textarea name="features" rows="4" placeholder="Bande passante illimitée&#10;Certificat SSL gratuit&#10;Support 24/7"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">{{ old('features') }}</textarea>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Tarification</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prix (€) <span class="text-red-500">*</span></label>
                <input type="number" name="price" value="{{ old('price', '0.00') }}" min="0" step="0.01" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Période <span class="text-red-500">*</span></label>
                <select name="billing_period" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="monthly" {{ old('billing_period') !== 'yearly' ? 'selected' : '' }}>Mensuel</option>
                    <option value="yearly" {{ old('billing_period') === 'yearly' ? 'selected' : '' }}>Annuel</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Ressources VM --}}
    <div x-show="planType === 'vm'" x-cloak class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Ressources</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">vCPU</label>
                <input type="number" name="cores" value="{{ old('cores') }}" min="1"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">RAM (MB)</label>
                <input type="number" name="memory_mb" value="{{ old('memory_mb') }}" min="128"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Disque (GB)</label>
                <input type="number" name="disk_gb" value="{{ old('disk_gb') }}" min="1"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>
    </div>

    {{-- Package CyberPanel --}}
    <div x-show="planType === 'hosting'" x-cloak class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Package CyberPanel</h2>
            <button type="button" @click="loadPackages()"
                class="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
                x-text="loadingPackages ? 'Chargement...' : '↻ Charger les packages'">
            </button>
        </div>
        <div x-show="packageError" class="text-xs text-red-500" x-text="packageError"></div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Package <span class="text-red-500">*</span></label>
            <template x-if="packages.length > 0">
                <select name="cyberpanel_package"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">— Sélectionnez un package —</option>
                    <template x-for="pkg in packages" :key="pkg">
                        <option :value="pkg" :selected="pkg === selectedPackage" x-text="pkg"></option>
                    </template>
                </select>
            </template>
            <template x-if="packages.length === 0">
                <input type="text" name="cyberpanel_package" x-model="selectedPackage"
                    placeholder="Default"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </template>
            <p class="text-xs text-gray-400 mt-1">Nom exact du package dans CyberPanel. Cliquez sur "Charger les packages" pour obtenir la liste.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Options</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}
                        class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm font-medium text-gray-700">Plan actif (visible aux clients)</span>
                </label>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            Créer le plan
        </button>
        <a href="{{ route('admin.plans.index') }}" class="text-sm text-gray-500 hover:underline">Annuler</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
function planForm(initialType, initialPackage) {
    return {
        planType: initialType,
        packages: [],
        selectedPackage: initialPackage || '',
        loadingPackages: false,
        packageError: '',
        loadPackages() {
            this.loadingPackages = true;
            this.packageError = '';
            fetch('{{ route('admin.plans.cyberpanel-packages') }}')
                .then(r => r.json())
                .then(d => {
                    if (d.success && d.packages && d.packages.length > 0) {
                        this.packages = d.packages;
                    } else {
                        this.packageError = d.message || 'Aucun package trouvé. Vérifiez la connexion CyberPanel dans les settings.';
                    }
                })
                .catch(() => { this.packageError = 'Erreur de connexion.'; })
                .finally(() => { this.loadingPackages = false; });
        }
    }
}
</script>
@endpush
