@extends('layouts.app')
@section('title', 'Commander — ' . $plan->name)
@section('sidebar')<x-client-sidebar />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('client.checkout.plans') }}" class="text-sm text-gray-400 hover:text-gray-600">← Nos offres</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Finaliser la commande</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <div class="lg:col-span-3">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Paiement sécurisé</h2>

            <div id="payment-error" class="hidden mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"></div>

            @if($stripeKey)
            <div id="payment-element" class="mb-5"></div>

            <button id="pay-btn" type="button"
                class="w-full py-3 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition disabled:opacity-60 disabled:cursor-not-allowed">
                Payer {{ number_format($plan->price, 2) }}€
            </button>

            <p class="text-xs text-gray-400 text-center mt-3">
                <svg class="w-3 h-3 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Paiement chiffré par Stripe
            </p>
            @else
            <div class="text-center py-8 text-gray-400">
                <p>Stripe n'est pas configuré.</p>
                <p class="text-sm mt-1">Contactez l'administrateur pour activer le paiement en ligne.</p>
            </div>
            @endif
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-6">
            <h2 class="font-semibold text-gray-800 mb-4">Récapitulatif</h2>

            <div class="border-b border-gray-100 pb-4 mb-4">
                <div class="font-bold text-gray-900">{{ $plan->name }}</div>
                @if($plan->description)
                <div class="text-sm text-gray-500 mt-0.5">{{ $plan->description }}</div>
                @endif
                <div class="text-xs text-gray-400 mt-1">{{ $plan->billing_period === 'yearly' ? 'Facturation annuelle' : 'Facturation mensuelle' }}</div>
            </div>

            @if($plan->cores || $plan->memory_mb || $plan->disk_gb)
            <div class="space-y-1.5 text-sm text-gray-600 mb-4 border-b border-gray-100 pb-4">
                @if($plan->cores)<div class="flex justify-between"><span>vCPU</span><span class="font-medium">{{ $plan->cores }}</span></div>@endif
                @if($plan->memory_mb)<div class="flex justify-between"><span>RAM</span><span class="font-medium">{{ $plan->memory_mb >= 1024 ? round($plan->memory_mb/1024, 0) . ' GB' : $plan->memory_mb . ' MB' }}</span></div>@endif
                @if($plan->disk_gb)<div class="flex justify-between"><span>Stockage</span><span class="font-medium">{{ $plan->disk_gb }} GB</span></div>@endif
            </div>
            @endif

            <div class="flex justify-between items-center text-lg font-bold text-gray-900">
                <span>Total</span>
                <span>{{ number_format($plan->price, 2) }}€</span>
            </div>
            <div class="text-xs text-gray-400 text-right">TVA non applicable</div>
        </div>
    </div>
</div>

@if($stripeKey)
@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
(async () => {
    const stripe = Stripe('{{ $stripeKey }}');

    const res = await fetch('{{ route('client.checkout.intent', $plan) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    });

    const data = await res.json();

    if (data.error) {
        document.getElementById('payment-error').textContent = data.error;
        document.getElementById('payment-error').classList.remove('hidden');
        document.getElementById('pay-btn').disabled = true;
        return;
    }

    const elements = stripe.elements({ clientSecret: data.client_secret });
    const paymentElement = elements.create('payment');
    paymentElement.mount('#payment-element');

    document.getElementById('pay-btn').addEventListener('click', async () => {
        const btn = document.getElementById('pay-btn');
        btn.disabled = true;
        btn.textContent = 'Traitement…';

        const { error } = await stripe.confirmPayment({
            elements,
            confirmParams: {
                return_url: '{{ route('client.checkout.success') }}'
            }
        });

        if (error) {
            const errEl = document.getElementById('payment-error');
            errEl.textContent = error.message;
            errEl.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = 'Payer {{ number_format($plan->price, 2) }}€';
        }
    });
})();
</script>
@endpush
@endif
@endsection
