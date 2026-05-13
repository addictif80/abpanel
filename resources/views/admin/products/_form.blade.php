@php $product = $product ?? null; @endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
    <h2 class="font-semibold text-gray-800">Informations produit</h2>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nom <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $product?->name) }}" required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none @error('name') border-red-400 @enderror">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Référence</label>
            <input type="text" name="reference" value="{{ old('reference', $product?->reference) }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea name="description" rows="3"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('description', $product?->description) }}</textarea>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Prix unitaire (€) <span class="text-red-500">*</span></label>
            <input type="number" name="unit_price" value="{{ old('unit_price', $product?->unit_price) }}"
                step="0.01" min="0" required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none @error('unit_price') border-red-400 @enderror">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Unité <span class="text-red-500">*</span></label>
            <select name="unit" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                @foreach(['forfait' => 'Forfait', 'hour' => 'Heure', 'day' => 'Jour', 'month' => 'Mois', 'year' => 'An', 'page' => 'Page'] as $val => $label)
                <option value="{{ $val }}" {{ old('unit', $product?->unit) === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Taux TVA (%)</label>
            <input type="number" name="tax_rate" value="{{ old('tax_rate', $product?->tax_rate ?? 0) }}"
                step="0.1" min="0" max="100"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <p class="text-xs text-gray-400 mt-1">0 = TVA non applicable (micro-entreprise)</p>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $product?->sort_order ?? 0) }}"
            class="w-32 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
    <h2 class="font-semibold text-gray-800">Visibilité</h2>

    <div class="flex items-center gap-3">
        <input type="checkbox" name="is_active" id="is_active" value="1"
            {{ old('is_active', $product?->is_active ?? true) ? 'checked' : '' }}
            class="rounded border-gray-300 text-indigo-600">
        <label for="is_active" class="text-sm text-gray-700">Produit actif (visible dans les devis)</label>
    </div>

    <div class="flex items-start gap-3">
        <input type="checkbox" name="hidden_from_pro" id="hidden_from_pro" value="1"
            {{ old('hidden_from_pro', $product?->hidden_from_pro) ? 'checked' : '' }}
            class="mt-0.5 rounded border-gray-300 text-indigo-600">
        <div>
            <label for="hidden_from_pro" class="text-sm text-gray-700 font-medium">Masqué aux clients professionnels</label>
            <p class="text-xs text-gray-400 mt-0.5">Ce produit ne sera pas proposé aux clients ayant renseigné un numéro SIRET.</p>
        </div>
    </div>
</div>
