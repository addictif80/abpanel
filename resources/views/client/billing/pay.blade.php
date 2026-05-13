@extends('layouts.app')
@section('title', 'Paiement — ' . $invoice->number)
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="max-w-lg mx-auto">
    <div class="mb-6">
        <a href="{{ route('client.billing.invoice', $invoice) }}" class="text-sm text-gray-400 hover:text-gray-600">← Retour à la facture</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Paiement en ligne</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
        <div class="flex justify-between text-sm text-gray-600 mb-1">
            <span>Facture</span><span class="font-mono font-medium">{{ $invoice->number }}</span>
        </div>
        @if($invoice->due_at)
        <div class="flex justify-between text-sm text-gray-600 mb-3">
            <span>Échéance</span><span>{{ $invoice->due_at->format('d/m/Y') }}</span>
        </div>
        @endif
        <div class="flex justify-between font-bold text-gray-900 text-lg border-t border-gray-100 pt-3">
            <span>Montant à régler</span><span>{{ number_format($invoice->total, 2) }} {{ $invoice->currency ?? 'EUR' }}</span>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" x-data="stripePayment()">

        <div id="payment-element" class="mb-5"></div>

        <div x-show="errorMsg" class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm" x-text="errorMsg"></div>

        <button @click="pay()" :disabled="loading"
            class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
            <span x-text="loading ? 'Traitement...' : 'Payer {{ number_format($invoice->total, 2) }} {{ $invoice->currency ?? 'EUR' }}'"></span>
        </button>

        <p class="text-xs text-gray-400 text-center mt-3">Paiement sécurisé via Stripe. Vos données bancaires ne sont jamais stockées.</p>
    </div>
</div>

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
function stripePayment() {
    return {
        stripe: null,
        elements: null,
        paymentElement: null,
        loading: false,
        errorMsg: '',

        async init() {
            this.stripe = Stripe('{{ $stripeKey }}');

            const res = await fetch('{{ route('client.billing.invoice.pay-intent', $invoice) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            const data = await res.json();

            if (data.error) {
                this.errorMsg = data.error;
                return;
            }

            this.elements = this.stripe.elements({ clientSecret: data.client_secret });
            this.paymentElement = this.elements.create('payment');
            this.paymentElement.mount('#payment-element');
        },

        async pay() {
            this.loading = true;
            this.errorMsg = '';

            const { error } = await this.stripe.confirmPayment({
                elements: this.elements,
                confirmParams: {
                    return_url: '{{ route('client.billing.invoice.pay-success', $invoice) }}',
                },
            });

            if (error) {
                this.errorMsg = error.message;
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
@endsection
