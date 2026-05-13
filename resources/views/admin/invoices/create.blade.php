@extends('layouts.app')
@section('title', 'Nouvelle facture')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.invoices.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Factures</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Nouvelle facture</h1>
</div>

@if($errors->any())
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('admin.invoices.store') }}" class="max-w-2xl space-y-5" x-data="invoiceForm()">
    @csrf

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Client &amp; échéance</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-500">*</span></label>
                <select name="user_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">— Choisir un client —</option>
                    @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ old('user_id') == $client->id ? 'selected' : '' }}>
                        {{ $client->full_name }} ({{ $client->email }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date d'échéance</label>
                <input type="date" name="due_date" value="{{ old('due_date') }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>

        {{-- Récurrence --}}
        <div class="border-t border-gray-100 pt-4" x-data="{ recurring: {{ old('is_recurring') ? 'true' : 'false' }} }">
            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer mb-3">
                <input type="checkbox" name="is_recurring" value="1" x-model="recurring"
                    {{ old('is_recurring') ? 'checked' : '' }}
                    class="rounded border-gray-300 text-indigo-600">
                Facture récurrente (génération automatique)
            </label>
            <div x-show="recurring" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Période</label>
                    <select name="recurrence_period" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="monthly" {{ old('recurrence_period') === 'monthly' ? 'selected' : '' }}>Mensuelle</option>
                        <option value="quarterly" {{ old('recurrence_period') === 'quarterly' ? 'selected' : '' }}>Trimestrielle</option>
                        <option value="yearly" {{ old('recurrence_period') === 'yearly' ? 'selected' : '' }}>Annuelle</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prochaine génération</label>
                    <input type="date" name="next_billing_at" value="{{ old('next_billing_at') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <p class="text-xs text-gray-400 mt-1">Date à laquelle la prochaine facture sera générée automatiquement.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Lignes de facturation</h2>
            <button type="button" @click="addLine()"
                class="text-sm text-indigo-600 hover:underline">+ Ajouter une ligne</button>
        </div>

        <div class="space-y-3">
            <template x-for="(line, index) in lines" :key="index">
                <div class="grid grid-cols-12 gap-2 items-start">
                    <div class="col-span-6">
                        <input type="text" :name="`items[${index}][description]`" x-model="line.description"
                            placeholder="Description" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div class="col-span-2">
                        <input type="number" :name="`items[${index}][quantity]`" x-model="line.quantity"
                            placeholder="Qté" min="0.01" step="0.01" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div class="col-span-3">
                        <input type="number" :name="`items[${index}][unit_price]`" x-model="line.unit_price"
                            placeholder="Prix €" min="0" step="0.01" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div class="col-span-1 flex justify-center pt-2">
                        <button type="button" @click="removeLine(index)" class="text-red-400 hover:text-red-600 text-lg leading-none">&times;</button>
                    </div>
                </div>
            </template>
        </div>

        <div class="flex justify-end border-t border-gray-100 pt-3">
            <div class="text-sm font-semibold text-gray-700">
                Total : <span class="text-lg text-gray-900" x-text="total() + ' €'"></span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            Créer la facture
        </button>
        <a href="{{ route('admin.invoices.index') }}" class="text-sm text-gray-500 hover:underline">Annuler</a>
    </div>
</form>

@push('scripts')
<script>
function invoiceForm() {
    return {
        lines: [{ description: '', quantity: 1, unit_price: 0 }],
        addLine() { this.lines.push({ description: '', quantity: 1, unit_price: 0 }); },
        removeLine(i) { if (this.lines.length > 1) this.lines.splice(i, 1); },
        total() {
            return this.lines.reduce((s, l) => s + (parseFloat(l.quantity) || 0) * (parseFloat(l.unit_price) || 0), 0).toFixed(2);
        }
    };
}
</script>
@endpush
@endsection
