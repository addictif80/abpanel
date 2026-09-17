{{--
    Shared "start billing retroactively" block for every import screen
    (VPS, hébergement, cloud/LDAP, domaine).

    Expects:
    - $plans: collection of Plan
    - $planRequired (optional, default false): true hides the "manual price" escape hatch (used by the cloud/LDAP import, which has no other way to price the resource)
--}}
@php $planRequired = $planRequired ?? false; @endphp
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4"
     x-data="{ planId: '{{ old('plan_id') }}', plans: {{ $plans->map(fn($p) => ['id' => $p->id, 'yearly' => $p->yearly_price !== null])->values()->toJson() }} }">
    <h2 class="font-semibold text-gray-800">Facturation</h2>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Plan tarifaire @if(!$planRequired)<span class="text-xs font-normal text-gray-400">(optionnel)</span>@else<span class="text-red-500">*</span>@endif
        </label>
        <select name="plan_id" x-model="planId" {{ $planRequired ? 'required' : '' }}
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            @if(!$planRequired)
            <option value="">— Aucun (prix manuel ci-dessus) —</option>
            @else
            <option value="">— Sélectionner un plan —</option>
            @endif
            @foreach($plans as $plan)
            <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>{{ $plan->name }} — {{ $plan->formattedPrice() }}</option>
            @endforeach
        </select>
        <p class="text-xs text-gray-400 mt-1">Si renseigné, une facture payée est créée rétroactivement à ce tarif et la facturation récurrente démarre automatiquement.</p>
    </div>

    <div x-show="planId && plans.some(p => p.id == planId && p.yearly)" x-cloak>
        <label class="block text-sm font-medium text-gray-700 mb-1">Période</label>
        <select name="billing_period" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <option value="monthly" {{ old('billing_period') !== 'yearly' ? 'selected' : '' }}>Mensuel</option>
            <option value="yearly" {{ old('billing_period') === 'yearly' ? 'selected' : '' }}>Annuel</option>
        </select>
    </div>

    <div x-show="planId" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Code promo <span class="text-xs font-normal text-gray-400">(optionnel)</span></label>
            <input type="text" name="promo_code" value="{{ old('promo_code') }}" placeholder="CODE10"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono uppercase focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date de paiement</label>
            <input type="date" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d')) }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <p class="text-xs text-gray-400 mt-1">Peut être une date passée (paiement reçu avant ABPanel).</p>
        </div>
    </div>
</div>
