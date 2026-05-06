@extends('layouts.app')
@section('title', 'Nouveau VPS')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.vms.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← VMs</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Créer un VPS</h1>
</div>

@if($errors->any())
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('admin.vms.store') }}" class="max-w-2xl space-y-5"
      x-data="vmCreateForm({{ json_encode($nodes) }}, {{ json_encode($osTemplates) }})"
      x-init="init()">
    @csrf

    {{-- Type selector --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="font-semibold text-gray-800 mb-4">Type de VPS</h2>
        <div class="grid grid-cols-2 gap-3">
            <label class="relative cursor-pointer">
                <input type="radio" name="vm_type" value="qemu" x-model="vmType" class="sr-only peer" {{ old('vm_type', 'qemu') === 'qemu' ? 'checked' : '' }}>
                <div class="rounded-xl border-2 p-4 transition peer-checked:border-indigo-500 peer-checked:bg-indigo-50 border-gray-200 hover:border-gray-300">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 text-sm">Machine virtuelle</div>
                            <div class="text-xs text-gray-500">KVM / QEMU — isolation complète</div>
                        </div>
                    </div>
                </div>
            </label>
            <label class="relative cursor-pointer">
                <input type="radio" name="vm_type" value="lxc" x-model="vmType" class="sr-only peer" {{ old('vm_type') === 'lxc' ? 'checked' : '' }}>
                <div class="rounded-xl border-2 p-4 transition peer-checked:border-indigo-500 peer-checked:bg-indigo-50 border-gray-200 hover:border-gray-300">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-teal-100 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 text-sm">Conteneur</div>
                            <div class="text-xs text-gray-500">LXC — léger, démarrage rapide</div>
                        </div>
                    </div>
                </div>
            </label>
        </div>
    </div>

    {{-- General --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Informations générales</h2>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-500">*</span></label>
            <select name="user_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">— Sélectionner un client —</option>
                @foreach($clients as $client)
                <option value="{{ $client->id }}" {{ old('user_id') == $client->id ? 'selected' : '' }}>
                    {{ $client->full_name }} ({{ $client->email }})
                </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <span x-text="vmType === 'lxc' ? 'Hostname' : 'Nom de la VM'"></span>
                    <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="50"
                    :placeholder="vmType === 'lxc' ? 'mon-container' : 'vm-client-web'"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nœud Proxmox <span class="text-red-500">*</span></label>
                <select name="proxmox_node" required x-model="selectedNode" @change="loadDiskStorages()"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">— Choisir un nœud —</option>
                    @foreach($nodes as $node)
                    <option value="{{ $node }}" {{ old('proxmox_node') === $node ? 'selected' : '' }}>{{ $node }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- OS Template --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">
                <span x-text="vmType === 'lxc' ? 'Template conteneur' : 'ISO d\'installation'"></span>
            </h2>
            <a href="{{ route('admin.os-templates.index') }}" target="_blank" class="text-xs text-indigo-600 hover:underline">Gérer les templates →</a>
        </div>

        <template x-if="availableTemplates.length > 0">
            <div>
                <select name="os_template_id"
                    :required="vmType === 'lxc'"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">
                        <span x-text="vmType === 'lxc' ? '— Template requis —' : '— Aucun ISO (installation manuelle) —'"></span>
                    </option>
                    <template x-for="tpl in availableTemplates" :key="tpl.id">
                        <option :value="tpl.id" x-text="tpl.name"></option>
                    </template>
                </select>
                <p x-show="vmType === 'lxc'" class="text-xs text-amber-600 mt-1">Obligatoire pour les conteneurs LXC.</p>
            </div>
        </template>

        <template x-if="availableTemplates.length === 0">
            <div class="px-4 py-3 bg-amber-50 border border-amber-100 rounded-lg text-xs text-amber-700">
                <span x-text="vmType === 'lxc' ? 'Aucun template LXC disponible. Ajoutez un template CT dans « Templates OS ».' : 'Aucun ISO disponible. La VM sera créée sans média de démarrage.'"></span>
            </div>
        </template>
    </div>

    {{-- Resources --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Ressources</h2>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">vCPU <span class="text-red-500">*</span></label>
                <input type="number" name="cores" value="{{ old('cores', 2) }}" min="1" max="128" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">RAM (MB) <span class="text-red-500">*</span></label>
                <input type="number" name="memory_mb" value="{{ old('memory_mb', 2048) }}" min="128" step="256" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div x-show="vmType === 'lxc'">
                <label class="block text-sm font-medium text-gray-700 mb-1">Swap (MB)</label>
                <input type="number" name="swap_mb" value="{{ old('swap_mb', 512) }}" min="0" step="256"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Disque (GB) <span class="text-red-500">*</span></label>
                <input type="number" name="disk_gb" value="{{ old('disk_gb', 20) }}" min="1" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Stockage disque <span class="text-red-500">*</span></label>
                <div x-show="loadingDisks" class="text-xs text-gray-400 py-2.5">Chargement…</div>
                <select x-show="!loadingDisks" name="disk_storage" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">— Choisir le stockage —</option>
                    <template x-for="s in diskStorages" :key="s.id">
                        <option :value="s.id" x-text="s.name"
                            :selected="'{{ old('disk_storage') }}' === s.id"></option>
                    </template>
                </select>
                <p x-show="!loadingDisks && selectedNode && diskStorages.length === 0" class="text-xs text-amber-600 mt-1">
                    Aucun stockage compatible trouvé sur ce nœud.
                </p>
            </div>
        </div>

        {{-- LXC unprivileged --}}
        <div x-show="vmType === 'lxc'">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="unprivileged" value="1" checked
                    class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm font-medium text-gray-700">Conteneur non-privilégié (recommandé)</span>
            </label>
        </div>
    </div>

    {{-- Network & billing --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Réseau &amp; facturation</h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">IP Tailscale</label>
                <input type="text" name="tailscale_ip" value="{{ old('tailscale_ip') }}" placeholder="100.x.x.x"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <p class="text-xs text-gray-400 mt-0.5">Crée la règle NPM automatiquement.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prix mensuel (€) <span class="text-red-500">*</span></label>
                <input type="number" name="monthly_price" value="{{ old('monthly_price', 0) }}" step="0.01" min="0" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition"
            x-text="vmType === 'lxc' ? 'Créer le conteneur' : 'Créer la VM'">
        </button>
        <a href="{{ route('admin.vms.index') }}" class="px-5 py-2.5 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition">
            Annuler
        </a>
    </div>
</form>

@push('scripts')
<script>
function vmCreateForm(nodes, osTemplates) {
    return {
        nodes,
        osTemplates, // { iso: [...], ct: [...] }
        vmType: '{{ old('vm_type', 'qemu') }}',
        selectedNode: '{{ old('proxmox_node', '') }}',
        diskStorages: [],
        loadingDisks: false,

        get availableTemplates() {
            const key = this.vmType === 'lxc' ? 'ct' : 'iso';
            return this.osTemplates[key] ?? [];
        },

        init() {
            this.$watch('vmType', () => this.loadDiskStorages());
            if (this.selectedNode) this.loadDiskStorages();
        },

        async loadDiskStorages() {
            if (!this.selectedNode) return;
            this.loadingDisks = true;
            this.diskStorages = [];
            try {
                const url = '{{ route('admin.vms.disk-storages') }}?node=' + encodeURIComponent(this.selectedNode) + '&vm_type=' + this.vmType;
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await res.json();
                if (!data.error) this.diskStorages = data;
            } catch {}
            finally { this.loadingDisks = false; }
        }
    };
}
</script>
@endpush
@endsection
