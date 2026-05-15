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
                        <div class="font-semibold text-sm text-gray-800">VPS (machine virtuelle)</div>
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
            <p class="text-xs text-gray-400 mt-1">Nom exact du package dans CyberPanel. Cliquez sur "Charger les packages" pour obtenir la liste.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <div>
            <h2 class="font-semibold text-gray-800">Limites & conditions d'achat</h2>
            <p class="text-sm text-gray-500 mt-0.5">Laissez vide pour aucune limite. La plus restrictive s'applique.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max par client</label>
                <input type="number" name="limit_per_client" value="{{ old('limit_per_client') }}"
                    min="1" placeholder="Illimité"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max par VPS détenu</label>
                <input type="number" name="limit_per_vm" value="{{ old('limit_per_vm') }}"
                    min="1" placeholder="Non limité"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max par hébergement détenu</label>
                <input type="number" name="limit_per_hosting" value="{{ old('limit_per_hosting') }}"
                    min="1" placeholder="Non limité"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max par domaine détenu</label>
                <input type="number" name="limit_per_domain" value="{{ old('limit_per_domain') }}"
                    min="1" placeholder="Non limité"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <p class="text-xs text-gray-400 mt-1">Ex : 1 → max = domaines apex configurés.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max par sous-domaine détenu</label>
                <input type="number" name="limit_per_subdomain" value="{{ old('limit_per_subdomain') }}"
                    min="1" placeholder="Non limité"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <p class="text-xs text-gray-400 mt-1">Ex : 2 → max = sous-domaines configurés × 2.</p>
            </div>
        </div>
        <div class="flex gap-6">
            <label class="flex items-center gap-2 cursor-pointer text-sm">
                <input type="checkbox" name="requires_vm" value="1" {{ old('requires_vm') ? 'checked' : '' }}
                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-gray-700">Requiert au moins 1 VPS actif</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer text-sm">
                <input type="checkbox" name="requires_hosting" value="1" {{ old('requires_hosting') ? 'checked' : '' }}
                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-gray-700">Requiert au moins 1 hébergement actif</span>
            </label>
        </div>

        <div x-data="allowancesEditor([], {{ Js::from($allPlans->map(fn($p) => ['id'=>$p->id,'label'=>$p->name.' ('.$p->type.')'])) }})" class="pt-2 border-t border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <p class="text-sm font-medium text-gray-700">Limite par plan possédé (optionnel)</p>
                    <p class="text-xs text-gray-400">Additive : chaque unité du plan sélectionné offre N commandes supplémentaires de ce produit.</p>
                </div>
                <button type="button" @click="addRow()"
                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium px-3 py-1 rounded hover:bg-indigo-50 transition">
                    + Ajouter une règle
                </button>
            </div>
            <input type="hidden" name="plan_allowances_json" :value="JSON.stringify(rows)">
            <div class="space-y-2">
                <template x-for="(row, i) in rows" :key="i">
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                        <span class="text-xs text-gray-500 shrink-0">1 unité de</span>
                        <select x-model.number="row.plan_id"
                            class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="0">— Choisir un plan —</option>
                            <template x-for="p in plans" :key="p.id">
                                <option :value="p.id" x-text="p.label"></option>
                            </template>
                        </select>
                        <span class="text-xs text-gray-500 shrink-0">offre</span>
                        <input type="number" x-model.number="row.limit_per_owned" min="1"
                            class="w-20 rounded-lg border border-gray-300 px-2 py-1.5 text-sm text-center focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <span class="text-xs text-gray-500 shrink-0">de ce produit</span>
                        <button type="button" @click="rows.splice(i, 1)"
                            class="text-red-400 hover:text-red-600 text-lg leading-none shrink-0">×</button>
                    </div>
                </template>
                <p x-show="rows.length === 0" class="text-xs text-gray-400 italic">Aucune règle par plan configurée.</p>
            </div>
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

function allowancesEditor(initialRows, plans) {
    return {
        rows: initialRows.length ? initialRows : [],
        plans: plans,
        addRow() {
            this.rows.push({ plan_id: 0, limit_per_owned: 1 });
        },
    };
}
</script>
@endpush
