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
      x-data="planForm('{{ old('type', $plan->type) }}', '{{ old('cyberpanel_package', $plan->cyberpanel_package) }}')">
    @csrf @method('PUT')

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Informations générales</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $plan->name) }}" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie <span class="text-red-500">*</span></label>
                <select name="type" required x-model="planType"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="vm" {{ old('type', $plan->type) === 'vm' ? 'selected' : '' }}>VPS / Serveur virtuel</option>
                    <option value="hosting" {{ old('type', $plan->type) === 'hosting' ? 'selected' : '' }}>Hébergement web</option>
                </select>
            </div>
        </div>

        <div x-show="planType === 'vm'" x-cloak>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type de virtualisation</label>
            <div class="grid grid-cols-2 gap-3">
                <label class="cursor-pointer">
                    <input type="radio" name="vm_type" value="qemu" class="sr-only peer" {{ old('vm_type', $plan->vm_type ?? 'qemu') === 'qemu' ? 'checked' : '' }}>
                    <div class="rounded-lg border-2 p-3 transition peer-checked:border-indigo-500 peer-checked:bg-indigo-50 border-gray-200 hover:border-gray-300">
                        <div class="font-semibold text-sm text-gray-800">VPS (machine virtuelle)</div>
                        <div class="text-xs text-gray-500 mt-0.5">KVM / QEMU — isolation complète</div>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="vm_type" value="lxc" class="sr-only peer" {{ old('vm_type', $plan->vm_type) === 'lxc' ? 'checked' : '' }}>
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
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Période <span class="text-red-500">*</span></label>
                <select name="billing_period" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="monthly" {{ old('billing_period', $plan->billing_period) !== 'yearly' ? 'selected' : '' }}>Mensuel</option>
                    <option value="yearly" {{ old('billing_period', $plan->billing_period) === 'yearly' ? 'selected' : '' }}>Annuel</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Stripe Price ID</label>
            <input type="text" name="stripe_price_id" value="{{ old('stripe_price_id', $plan->stripe_price_id) }}"
                placeholder="price_xxxxxxxxxxxx"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>
    </div>

    {{-- Ressources VM --}}
    <div x-show="planType === 'vm'" x-cloak class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
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
                <div>
                    <select name="cyberpanel_package" x-model="selectedPackage"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">— Sélectionnez un package —</option>
                        <template x-for="pkg in packages" :key="pkg.packageName">
                            <option :value="pkg.packageName" x-text="pkg.packageName"></option>
                        </template>
                    </select>
                    <template x-if="selectedPackage">
                        <div class="mt-2 p-3 bg-gray-50 rounded-lg text-xs text-gray-600 grid grid-cols-3 gap-2" x-show="selectedPackageData">
                            <span>💾 <span x-text="selectedPackageData?.diskSpace ?? '—'"></span> MB disque</span>
                            <span>🗄️ <span x-text="selectedPackageData?.dataBases ?? '—'"></span> bases de données</span>
                            <span>🌐 <span x-text="selectedPackageData?.allowedDomains ?? '—'"></span> domaines</span>
                        </div>
                    </template>
                </div>
            </template>
            <template x-if="packages.length === 0">
                <input type="text" name="cyberpanel_package" x-model="selectedPackage"
                    placeholder="Default"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </template>
            <p class="text-xs text-gray-400 mt-1">Package actuel : <strong>{{ $plan->cyberpanel_package ?: '(non défini)' }}</strong>. Cliquez sur "Charger les packages" pour changer.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <div>
            <h2 class="font-semibold text-gray-800">Limites & conditions d'achat</h2>
            <p class="text-sm text-gray-500 mt-0.5">Laissez vide pour aucune limite. Les limites par ressource se cumulent : la plus restrictive s'applique.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max par client</label>
                <input type="number" name="limit_per_client" value="{{ old('limit_per_client', $plan->limit_per_client) }}"
                    min="1" placeholder="Illimité"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <p class="text-xs text-gray-400 mt-1">Ex : 3 → max 3 de ce produit par client.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max par VPS détenu</label>
                <input type="number" name="limit_per_vm" value="{{ old('limit_per_vm', $plan->limit_per_vm) }}"
                    min="1" placeholder="Non limité par VPS"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <p class="text-xs text-gray-400 mt-1">Ex : 1 → max = nombre de VPS actifs.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max par hébergement détenu</label>
                <input type="number" name="limit_per_hosting" value="{{ old('limit_per_hosting', $plan->limit_per_hosting) }}"
                    min="1" placeholder="Non limité par site"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <p class="text-xs text-gray-400 mt-1">Ex : 2 → max = hébergements actifs × 2.</p>
            </div>
        </div>

        <div class="flex gap-6 pt-1">
            <label class="flex items-center gap-2 cursor-pointer text-sm">
                <input type="checkbox" name="requires_vm" value="1"
                    {{ old('requires_vm', $plan->requires_vm) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-gray-700">Requiert au moins 1 VPS actif</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer text-sm">
                <input type="checkbox" name="requires_hosting" value="1"
                    {{ old('requires_hosting', $plan->requires_hosting) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-gray-700">Requiert au moins 1 hébergement actif</span>
            </label>
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
                    <span class="text-sm font-medium text-gray-700">Plan actif (visible aux clients)</span>
                </label>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            Enregistrer
        </button>
        <a href="{{ route('admin.plans.index') }}" class="text-sm text-gray-500 hover:underline">Annuler</a>

        <div class="ml-auto">
            <button type="button" form="delete-plan-form"
                    onclick="if(confirm('Supprimer définitivement ce plan ?')) document.getElementById('delete-plan-form').submit()"
                    class="px-4 py-2 bg-red-50 text-red-600 text-sm font-semibold rounded-lg hover:bg-red-100 transition">
                Supprimer
            </button>
        </div>
    </div>
</form>

<form id="delete-plan-form" method="POST" action="{{ route('admin.plans.destroy', $plan) }}">
    @csrf @method('DELETE')
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
        get selectedPackageData() {
            return this.packages.find(p => p.packageName === this.selectedPackage) ?? null;
        },
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
