@php
    $quote    = $quote ?? null;
    $isEdit   = $quote !== null;
    $items    = old('items', $quote ? $quote->items->map(fn($i) => [
        'product_id'      => $i->product_id,
        'description'     => $i->description,
        'details'         => $i->details,
        'quantity'        => $i->quantity,
        'unit'            => $i->unit,
        'unit_price'      => $i->unit_price,
        'discount_amount' => $i->discount_amount,
        'discount_type'   => $i->discount_type,
        'tax_rate'        => $i->tax_rate,
    ])->toArray() : ($fromTemplate ? $fromTemplate->items->map(fn($i) => [
        'product_id'      => $i->product_id,
        'description'     => $i->description,
        'details'         => $i->details,
        'quantity'        => $i->quantity,
        'unit'            => $i->unit,
        'unit_price'      => $i->unit_price,
        'discount_amount' => $i->discount_amount,
        'discount_type'   => $i->discount_type,
        'tax_rate'        => $i->tax_rate,
    ])->toArray() : [['description' => '', 'quantity' => 1, 'unit' => 'forfait', 'unit_price' => 0, 'discount_amount' => 0, 'discount_type' => 'fixed', 'tax_rate' => 0]]));
@endphp

<div x-data="quoteForm({{ json_encode($items) }}, {{ json_encode($products->map(fn($p) => ['id' => $p->id, 'description' => $p->name, 'unit_price' => $p->unit_price, 'unit' => $p->unit, 'tax_rate' => $p->tax_rate])) }})" class="space-y-5">

{{-- Client & Header --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
    <h2 class="font-semibold text-gray-800">Informations générales</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @if(!$isEdit)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-500">*</span></label>
            <select name="user_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">— Choisir un client —</option>
                @foreach($clients as $client)
                <option value="{{ $client->id }}" {{ old('user_id') == $client->id ? 'selected' : '' }}>
                    {{ $client->full_name }} ({{ $client->email }})
                    @if($client->isPro()) — PRO @endif
                </option>
                @endforeach
            </select>
        </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Objet du devis</label>
            <input type="text" name="subject" value="{{ old('subject', $quote?->subject ?? $fromTemplate?->subject) }}"
                placeholder="Ex: Création site vitrine..."
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date d'expiration</label>
            <input type="date" name="expires_at"
                value="{{ old('expires_at', $quote?->expires_at?->format('Y-m-d') ?? now()->addDays($validityDays ?? 30)->format('Y-m-d')) }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <p class="text-xs text-gray-400 mt-1">Défaut : {{ $validityDays ?? 30 }} jours (configurable dans les paramètres)</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Acompte (%)</label>
            <input type="number" name="deposit_percent" x-model="depositPercent"
                value="{{ old('deposit_percent', $quote?->deposit_percent ?? 0) }}"
                min="0" max="100" step="1"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <p class="text-xs text-gray-400 mt-1" x-show="depositPercent > 0">
                Montant acompte : <strong x-text="formatAmount(depositAmount())"></strong> — Solde : <strong x-text="formatAmount(total() - depositAmount())"></strong>
            </p>
        </div>
    </div>
</div>

{{-- Line items --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-gray-800">Lignes du devis</h2>
        <div class="flex gap-2">
            @if($products->count())
            <select @change="addFromProduct($event.target.value); $event.target.value=''"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">+ Ajouter depuis le catalogue</option>
                @foreach($products as $p)
                <option value="{{ $p->id }}" data-price="{{ $p->unit_price }}" data-unit="{{ $p->unit }}" data-tax="{{ $p->tax_rate }}">
                    {{ $p->name }} — {{ number_format($p->unit_price, 2) }}€
                </option>
                @endforeach
            </select>
            @endif
            <button type="button" @click="addLine()"
                class="px-3 py-2 text-sm text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50">
                + Ligne vide
            </button>
        </div>
    </div>

    <div class="space-y-3">
        {{-- En-tête des colonnes --}}
        <div class="grid grid-cols-12 gap-2 px-3">
            <div class="col-span-5 text-xs font-semibold text-gray-400 uppercase tracking-wide">Description</div>
            <div class="col-span-1 text-xs font-semibold text-gray-400 uppercase tracking-wide">Qté</div>
            <div class="col-span-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">Unité</div>
            <div class="col-span-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">Prix unit. (€)</div>
            <div class="col-span-2 text-xs font-semibold text-gray-400 uppercase tracking-wide"></div>
        </div>

        <template x-for="(line, index) in lines" :key="index">
            <div class="border border-gray-100 rounded-lg p-3 bg-gray-50 space-y-2">
                <div class="grid grid-cols-12 gap-2 items-start">
                    <div class="col-span-5">
                        <input type="text" :name="`items[${index}][description]`" x-model="line.description"
                            placeholder="Ex : Création page d'accueil" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <input type="hidden" :name="`items[${index}][product_id]`" x-model="line.product_id">
                    </div>
                    <div class="col-span-1">
                        <input type="number" :name="`items[${index}][quantity]`" x-model="line.quantity"
                            placeholder="1" min="0.01" step="0.01" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div class="col-span-2">
                        <select :name="`items[${index}][unit]`" x-model="line.unit"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="forfait">Forfait</option>
                            <option value="hour">Heure</option>
                            <option value="day">Jour</option>
                            <option value="month">Mois</option>
                            <option value="year">An</option>
                            <option value="page">Page</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <input type="number" :name="`items[${index}][unit_price]`" x-model="line.unit_price"
                            placeholder="0.00" min="0" step="0.01" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div class="col-span-1 flex justify-end pt-1">
                        <button type="button" @click="removeLine(index)" class="text-red-400 hover:text-red-600 text-xl leading-none">&times;</button>
                    </div>
                </div>

                {{-- Details & discount --}}
                <div class="grid grid-cols-12 gap-2 items-center">
                    <div class="col-span-5">
                        <input type="text" :name="`items[${index}][details]`" x-model="line.details"
                            placeholder="Détails (optionnel)"
                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div class="col-span-2 flex gap-1">
                        <input type="number" :name="`items[${index}][discount_amount]`" x-model="line.discount_amount"
                            placeholder="Remise" min="0" step="0.01"
                            class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <select :name="`items[${index}][discount_type]`" x-model="line.discount_type"
                            class="rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="fixed">€</option>
                            <option value="percent">%</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <input type="number" :name="`items[${index}][tax_rate]`" x-model="line.tax_rate"
                            placeholder="TVA %" min="0" max="100" step="0.1"
                            class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div class="col-span-3 text-right text-sm font-semibold text-gray-800">
                        <span x-text="formatAmount(lineTotal(line))"></span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Global discount & totals --}}
    <div class="border-t border-gray-100 pt-4 flex justify-end">
        <div class="w-72 space-y-2">
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <span class="flex-1">Remise globale</span>
                <input type="number" name="discount_amount" x-model="globalDiscount" placeholder="0" min="0" step="0.01"
                    class="w-24 rounded-lg border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <select name="discount_type" x-model="globalDiscountType"
                    class="rounded-lg border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="fixed">€</option>
                    <option value="percent">%</option>
                </select>
            </div>
            <div class="flex justify-between text-sm text-gray-600 py-1">
                <span>Sous-total HT</span>
                <span x-text="formatAmount(subtotal())"></span>
            </div>
            <div class="flex justify-between text-sm text-gray-600 py-1" x-show="Number(globalDiscount) > 0">
                <span>Remise globale</span>
                <span class="text-red-500" x-text="'- ' + formatAmount(globalDiscountAmount())"></span>
            </div>
            <div class="flex justify-between font-bold text-gray-900 border-t border-gray-200 pt-2 text-base">
                <span>Total</span>
                <span x-text="formatAmount(total())"></span>
            </div>
            <p class="text-xs text-gray-400 text-right">TVA non applicable, art. 293 B du CGI</p>
        </div>
    </div>
</div>

{{-- Notes --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
    <h2 class="font-semibold text-gray-800">Notes & remarques</h2>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notes visibles par le client</label>
        <textarea name="notes" rows="3"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('notes', $quote?->notes ?? $defaultNotes ?? '') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notes internes (non visibles par le client)</label>
        <textarea name="internal_notes" rows="2"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('internal_notes', $quote?->internal_notes) }}</textarea>
    </div>
</div>

</div>{{-- end x-data --}}

@push('scripts')
<script>
function quoteForm(initialItems, catalog) {
    return {
        lines: initialItems.length ? initialItems.map(i => ({...i})) : [{ description:'', quantity:1, unit:'forfait', unit_price:0, discount_amount:0, discount_type:'fixed', tax_rate:0, product_id:null, details:'' }],
        catalog,
        globalDiscount: '{{ old('discount_amount', $quote?->discount_amount ?? 0) }}',
        globalDiscountType: '{{ old('discount_type', $quote?->discount_type ?? 'fixed') }}',
        depositPercent: '{{ old('deposit_percent', $quote?->deposit_percent ?? 0) }}',

        addLine() {
            this.lines.push({ description:'', quantity:1, unit:'forfait', unit_price:0, discount_amount:0, discount_type:'fixed', tax_rate:0, product_id:null, details:'' });
        },
        removeLine(i) {
            if (this.lines.length > 1) this.lines.splice(i, 1);
        },
        addFromProduct(id) {
            if (!id) return;
            const p = this.catalog.find(p => p.id == id);
            if (p) this.lines.push({ product_id: p.id, description: p.description, quantity:1, unit: p.unit || 'forfait', unit_price: p.unit_price, discount_amount:0, discount_type:'fixed', tax_rate: p.tax_rate || 0, details:'' });
        },
        lineTotal(line) {
            const base = (parseFloat(line.quantity) || 0) * (parseFloat(line.unit_price) || 0);
            const disc = line.discount_type === 'percent'
                ? base * ((parseFloat(line.discount_amount) || 0) / 100)
                : (parseFloat(line.discount_amount) || 0);
            return Math.max(0, base - disc);
        },
        subtotal() {
            return this.lines.reduce((s, l) => s + this.lineTotal(l), 0);
        },
        globalDiscountAmount() {
            const sub = this.subtotal();
            return this.globalDiscountType === 'percent'
                ? sub * ((parseFloat(this.globalDiscount) || 0) / 100)
                : (parseFloat(this.globalDiscount) || 0);
        },
        total() {
            return Math.max(0, this.subtotal() - this.globalDiscountAmount());
        },
        depositAmount() {
            return this.total() * ((parseFloat(this.depositPercent) || 0) / 100);
        },
        formatAmount(v) {
            return Number(v).toFixed(2) + ' €';
        }
    };
}
</script>
@endpush
