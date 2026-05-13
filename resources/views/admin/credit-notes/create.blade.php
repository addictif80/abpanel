@extends('layouts.app')
@section('title', 'Nouvel avoir')
@section('sidebar')<x-admin-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.credit-notes.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Avoirs</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Nouvel avoir</h1>
</div>

<form method="POST" action="{{ route('admin.credit-notes.store') }}" class="max-w-xl space-y-5"
      x-data="creditNoteForm()" >
    @csrf

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Sélection</h2>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-500">*</span></label>
            <select x-model="clientId" @change="loadInvoices()" name="_client_id"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">— Choisir un client —</option>
                @foreach($clients as $client)
                <option value="{{ $client->id }}" {{ optional($selectedClient)->id == $client->id ? 'selected' : '' }}>
                    {{ $client->full_name }} ({{ $client->email }})
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Facture concernée <span class="text-red-500">*</span></label>
            <select name="invoice_id" x-model="invoiceId" required
                :disabled="!invoices.length"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-400">
                <option value="">— Sélectionner une facture payée —</option>
                <template x-for="inv in invoices" :key="inv.id">
                    <option :value="inv.id" x-text="inv.number + ' — ' + parseFloat(inv.total).toFixed(2) + ' ' + inv.currency"></option>
                </template>
            </select>
            <p class="text-xs text-gray-400 mt-1">Seules les factures payées sont listées.</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Montant à créditer (€) <span class="text-red-500">*</span></label>
                <input type="number" name="amount" min="0.01" step="0.01" required
                    value="{{ old('amount') }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Motif</label>
            <textarea name="reason" rows="3"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('reason') }}</textarea>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
            Créer l'avoir
        </button>
        <a href="{{ route('admin.credit-notes.index') }}" class="text-sm text-gray-500 hover:underline">Annuler</a>
    </div>
</form>

@push('scripts')
<script>
function creditNoteForm() {
    return {
        clientId: '{{ optional($selectedClient)->id }}',
        invoiceId: '',
        invoices: @json($invoices),
        loadInvoices() {
            if (!this.clientId) { this.invoices = []; return; }
            fetch(`{{ route('admin.credit-notes.invoices') }}?user_id=${this.clientId}`)
                .then(r => r.json())
                .then(data => { this.invoices = data; this.invoiceId = ''; });
        }
    };
}
</script>
@endpush
@endsection
