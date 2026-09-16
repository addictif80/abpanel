@extends('layouts.app')
@section('title', 'Importer ' . $ldapUser['uid'])
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.ldap.import.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Importer un compte LDAP</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">
        Importer « {{ $ldapUser['cn'] ?: $ldapUser['uid'] }} »
        <span class="text-base font-normal text-gray-400 ml-2 font-mono">{{ $ldapUser['uid'] }}</span>
    </h1>
</div>

@if($errors->any())
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

@if($plans->isEmpty())
<div class="mb-4 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-sm">
    Aucun plan de type "Service" avec un groupe LDAP n'existe encore. Crée d'abord un plan (Admin → Tarifs → Nouveau plan → catégorie "Service") avant de pouvoir importer ce compte.
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Form --}}
    <div class="lg:col-span-2">
        <form method="POST" action="{{ route('admin.ldap.import.store', $ldapUser['uid']) }}" class="space-y-5"
              x-data="{ planId: '{{ old('plan_id') }}', plans: {{ $plans->map(fn($p) => ['id' => $p->id, 'yearly' => $p->yearly_price !== null])->values()->toJson() }} }">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                <h2 class="font-semibold text-gray-800">Assignation</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-500">*</span></label>
                    <select name="user_id" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">— Sélectionner un client —</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('user_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->full_name }} — {{ $client->email }}
                            @if($client->company) ({{ $client->company }})@endif
                        </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Si ce client a déjà un compte LDAP différent rattaché, l'import sera refusé.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Plan <span class="text-red-500">*</span></label>
                    <select name="plan_id" x-model="planId" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">— Sélectionner un plan —</option>
                        @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->name }} — {{ $plan->formattedPrice() }} (groupe LDAP : {{ $plan->ldap_group }})
                        </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Détermine le tarif, le groupe LDAP et le quota Synology appliqués.</p>
                </div>

                <div x-show="plans.some(p => p.id == planId && p.yearly)" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Période de facturation</label>
                    <select name="billing_period" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="monthly" {{ old('billing_period') !== 'yearly' ? 'selected' : '' }}>Mensuel</option>
                        <option value="yearly" {{ old('billing_period') === 'yearly' ? 'selected' : '' }}>Annuel</option>
                    </select>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-800">
                À l'import : une facture est créée <strong>marquée payée</strong> pour démarrer la facturation récurrente (mensuelle ou annuelle selon le plan), le compte LDAP est rattaché à ce client, et son groupe/quota sont synchronisés — sans recréer ni modifier le compte LDAP existant.
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" {{ $plans->isEmpty() ? 'disabled' : '' }}
                    class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    Importer et démarrer la facturation
                </button>
                <a href="{{ route('admin.ldap.import.index') }}" class="text-sm text-gray-500 hover:underline">Annuler</a>
            </div>
        </form>
    </div>

    {{-- LDAP info card --}}
    <div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sticky top-6">
            <h2 class="font-semibold text-gray-800 mb-4">Informations LDAP</h2>
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">uid</dt>
                    <dd class="font-mono font-medium text-gray-800">{{ $ldapUser['uid'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">cn</dt>
                    <dd class="font-medium text-gray-800">{{ $ldapUser['cn'] ?: '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">mail</dt>
                    <dd class="font-medium text-gray-800">{{ $ldapUser['mail'] ?: '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">uidNumber</dt>
                    <dd class="font-mono font-medium text-gray-800">{{ $ldapUser['uidNumber'] ?: '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

</div>
@endsection
