@extends('layouts.app')
@section('title', 'Importer hôte NPM #' . $host['id'])
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.domains.import.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Importer un hôte NPM</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">
        Importer « {{ $host['domain_names'][0] ?? '—' }} »
        <span class="text-base font-normal text-gray-400 ml-2">#{{ $host['id'] }}</span>
    </h1>
</div>

@if($errors->any())
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6"
     x-data="importDomainForm({{ json_encode($host['domain_names'] ?? []) }})">

    {{-- Form --}}
    <div class="lg:col-span-2">
        <form method="POST" action="{{ route('admin.domains.import.store', $host['id']) }}" class="space-y-5">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                <h2 class="font-semibold text-gray-800">Assignation</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Domaine à importer <span class="text-red-500">*</span></label>
                    @if(count($host['domain_names'] ?? []) > 1)
                    <select name="domain" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        @foreach($host['domain_names'] as $name)
                        <option value="{{ $name }}" {{ old('domain') === $name ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Cet hôte NPM sert plusieurs domaines — un seul est importé comme domaine principal ici.</p>
                    @else
                    <input type="text" name="domain" value="{{ old('domain', $host['domain_names'][0] ?? '') }}" required readonly
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-gray-50 font-mono">
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-500">*</span></label>
                    <select name="user_id" x-model="userId" @change="loadResources()" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">— Sélectionner un client —</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('user_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->full_name }} — {{ $client->email }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                    <select name="type" x-model="type" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="vps" {{ old('type') === 'vps' ? 'selected' : '' }}>VPS</option>
                        <option value="hosting" {{ old('type', 'hosting') === 'hosting' ? 'selected' : '' }}>Hébergement</option>
                    </select>
                </div>

                <div x-show="type === 'vps'" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 mb-1">VPS lié <span class="text-xs font-normal text-gray-400">(optionnel)</span></label>
                    <select name="virtual_machine_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">— Aucun —</option>
                        <template x-for="vm in resources.virtual_machines" :key="vm.id">
                            <option :value="vm.id" x-text="vm.name" :selected="vm.id == {{ old('virtual_machine_id', 0) }}"></option>
                        </template>
                    </select>
                    <p class="text-xs text-gray-400 mt-1" x-show="userId && resources.virtual_machines.length === 0">Ce client n'a aucun VPS enregistré dans le panel.</p>
                </div>

                <div x-show="type === 'hosting'" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hébergement lié <span class="text-xs font-normal text-gray-400">(optionnel)</span></label>
                    <select name="hosting_account_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">— Aucun —</option>
                        <template x-for="h in resources.hosting_accounts" :key="h.id">
                            <option :value="h.id" x-text="h.domain" :selected="h.id == {{ old('hosting_account_id', 0) }}"></option>
                        </template>
                    </select>
                    <p class="text-xs text-gray-400 mt-1" x-show="userId && resources.hosting_accounts.length === 0">Ce client n'a aucun hébergement enregistré dans le panel.</p>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-800">
                L'hôte NPM n'est ni recréé ni modifié — seule sa référence (cible, SSL) est enregistrée dans le panel pour l'affichage client et le suivi.
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    Importer et assigner
                </button>
                <a href="{{ route('admin.domains.import.index') }}" class="text-sm text-gray-500 hover:underline">Annuler</a>
            </div>
        </form>
    </div>

    {{-- NPM info card --}}
    <div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sticky top-6">
            <h2 class="font-semibold text-gray-800 mb-4">Informations NPM</h2>
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">ID</dt>
                    <dd class="font-mono font-medium text-gray-800">{{ $host['id'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Cible</dt>
                    <dd class="font-mono font-medium text-gray-800 text-xs">{{ $host['forward_scheme'] ?? '?' }}://{{ $host['forward_host'] ?? '?' }}:{{ $host['forward_port'] ?? '?' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">SSL</dt>
                    <dd class="font-medium text-gray-800">{{ !empty($host['certificate_id']) ? 'Activé' : 'Aucun' }}</dd>
                </div>
                @if(count($host['domain_names'] ?? []) > 0)
                <div class="pt-2 border-t border-gray-100">
                    <dt class="text-gray-500 mb-1">Domaines servis</dt>
                    <dd class="text-gray-600 text-xs space-y-0.5">
                        @foreach($host['domain_names'] as $name)
                        <div class="font-mono">{{ $name }}</div>
                        @endforeach
                    </dd>
                </div>
                @endif
            </dl>
        </div>
    </div>
</div>

@push('scripts')
<script>
function importDomainForm(domainNames) {
    return {
        userId: '{{ old('user_id', '') }}',
        type: '{{ old('type', 'hosting') }}',
        resources: { virtual_machines: [], hosting_accounts: [] },
        async loadResources() {
            if (!this.userId) { this.resources = { virtual_machines: [], hosting_accounts: [] }; return; }
            try {
                const res = await fetch('{{ url('admin/domains/import/client-resources') }}/' + this.userId);
                this.resources = await res.json();
            } catch (e) {}
        },
        init() {
            if (this.userId) this.loadResources();
        }
    };
}
</script>
@endpush
@endsection
