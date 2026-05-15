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

            @if($limitError ?? null)
            <div class="mb-4 px-4 py-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800 flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="font-medium">Commande non disponible</p>
                    <p class="mt-0.5">{{ $limitError }}</p>
                </div>
            </div>
            @endif

            @if($plan->type === 'hosting')
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Domaine à héberger <span class="text-red-500">*</span>
                </label>
                <div class="flex rounded-lg border border-gray-300 overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500">
                    <span class="px-3 py-2 bg-gray-50 text-gray-500 text-sm border-r border-gray-300 select-none">https://</span>
                    <input type="text" id="domain-input" placeholder="monsite.fr"
                        class="flex-1 px-3 py-2 text-sm focus:outline-none"
                        autocomplete="off" spellcheck="false">
                </div>
                <p class="text-xs text-gray-400 mt-1">Saisissez le nom de domaine sans http:// ni www.</p>
            </div>
            @endif

            @if($stripeKey && !($limitError ?? null))
            <div class="mb-5" x-data="promoBlock()">
                <label class="block text-sm font-medium text-gray-700 mb-1">Code promo</label>
                <div class="flex gap-2">
                    <input type="text" x-model="code" placeholder="CODE10"
                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono uppercase focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        :disabled="applied !== null"
                        @keydown.enter.prevent="applyCode()">
                    <button type="button" @click="applyCode()"
                        :disabled="loading || !code || applied !== null"
                        class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition disabled:opacity-50">
                        <span x-text="loading ? '…' : (applied ? '✓' : 'Appliquer')"></span>
                    </button>
                    <button x-show="applied" type="button" @click="removeCode()"
                        class="px-3 py-2 text-red-400 hover:text-red-600 text-sm">✕</button>
                </div>
                <p x-show="error" class="mt-1 text-xs text-red-600" x-text="error"></p>
                <p x-show="applied" class="mt-1 text-xs text-green-600">
                    Code appliqué : <span class="font-semibold" x-text="applied?.label"></span>
                    — Nouveau total : <span class="font-semibold" x-text="applied?.newTotal + '€'"></span>
                </p>
            </div>

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

@if($stripeKey && !($limitError ?? null))
@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
(function () {
    const stripe = Stripe('{{ $stripeKey }}');
    const btn    = document.getElementById('pay-btn');
    const errEl  = document.getElementById('payment-error');
    const PLAN_PRICE = {{ $plan->price }};
    const PLAN_ID    = {{ $plan->id }};
    const INTENT_URL = '{{ route('client.checkout.intent', $plan) }}';
    const SUCCESS_URL = '{{ route('client.checkout.success') }}';
    const CSRF = '{{ csrf_token() }}';

    let currentElements = null;
    let currentPaymentEl = null;
    let appliedPromoCode = null;

    function fmtPrice(amount) {
        return 'Payer ' + amount.toFixed(2).replace('.', ',') + '€';
    }

    function showError(msg) {
        errEl.textContent = msg;
        errEl.classList.remove('hidden');
    }

    function hideError() { errEl.classList.add('hidden'); }

    // ── VPS : créer / recréer l'intent et monter le payment element ──────
    @if($plan->type !== 'hosting')
    async function loadIntent(promoCode = null) {
        hideError();
        btn.disabled = true;
        btn.textContent = 'Chargement…';
        document.getElementById('payment-element').innerHTML = '';
        currentElements = null;

        try {
            const body = promoCode ? { promo_code: promoCode } : {};
            const res  = await fetch(INTENT_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify(body),
            });
            const data = await res.json();

            if (data.error) { showError(data.error); btn.disabled = true; return; }

            currentElements = stripe.elements({ clientSecret: data.client_secret });
            currentPaymentEl = currentElements.create('payment');
            currentPaymentEl.mount('#payment-element');
            currentPaymentEl.on('ready', () => {
                btn.disabled = false;
                btn.textContent = promoCode
                    ? fmtPrice(Math.max(0, PLAN_PRICE - (window._promoDiscount || 0)))
                    : fmtPrice(PLAN_PRICE);
            });
        } catch (e) {
            showError('Erreur d\'initialisation du paiement. Rechargez la page.');
            btn.disabled = true;
        }
    }

    // Exposer pour que promoBlock() puisse déclencher le rechargement
    window._reloadStripeIntent = (promoCode, discount) => {
        window._promoDiscount = discount || 0;
        appliedPromoCode = promoCode;
        loadIntent(promoCode);
    };
    window._reloadStripeIntentNoPromo = () => {
        window._promoDiscount = 0;
        appliedPromoCode = null;
        loadIntent(null);
    };

    loadIntent();

    btn.addEventListener('click', async () => {
        if (!currentElements) return;
        hideError();
        btn.disabled = true;
        btn.textContent = 'Traitement…';
        const { error } = await stripe.confirmPayment({
            elements: currentElements,
            confirmParams: { return_url: SUCCESS_URL },
        });
        if (error) {
            showError(error.message);
            btn.disabled = false;
            btn.textContent = appliedPromoCode
                ? fmtPrice(Math.max(0, PLAN_PRICE - (window._promoDiscount || 0)))
                : fmtPrice(PLAN_PRICE);
        }
    });

    @else
    // ── Hébergement : intent créé au clic (domaine + promo) ───────────────
    const domainInput = document.getElementById('domain-input');

    btn.addEventListener('click', async () => {
        hideError();
        const domain = domainInput.value.trim();
        if (!domain) { showError('Veuillez saisir le domaine à héberger.'); return; }

        btn.disabled = true;
        btn.textContent = 'Initialisation…';

        try {
            const body = { domain };
            if (appliedPromoCode) body.promo_code = appliedPromoCode;

            const res  = await fetch(INTENT_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify(body),
            });
            const data = await res.json();
            if (data.error) { showError(data.error); btn.disabled = false; btn.textContent = fmtPrice(PLAN_PRICE); return; }

            currentElements = stripe.elements({ clientSecret: data.client_secret });
            currentPaymentEl = currentElements.create('payment');
            currentPaymentEl.mount('#payment-element');

            await new Promise(resolve => currentPaymentEl.on('ready', resolve));

            const { error } = await stripe.confirmPayment({
                elements: currentElements,
                confirmParams: { return_url: SUCCESS_URL },
            });
            if (error) {
                showError(error.message);
                btn.disabled = false;
                btn.textContent = fmtPrice(PLAN_PRICE);
            }
        } catch (e) {
            showError('Une erreur est survenue. Veuillez réessayer.');
            btn.disabled = false;
            btn.textContent = fmtPrice(PLAN_PRICE);
        }
    });

    window._reloadStripeIntent = (promoCode, discount) => {
        window._promoDiscount = discount || 0;
        appliedPromoCode = promoCode;
    };
    window._reloadStripeIntentNoPromo = () => {
        window._promoDiscount = 0;
        appliedPromoCode = null;
    };
    @endif

    // ── Alpine.js : bloc code promo ───────────────────────────────────────
    window.promoBlock = function () {
        return {
            code: '',
            applied: null,
            loading: false,
            error: '',
            async applyCode() {
                this.error = '';
                if (!this.code) return;
                this.loading = true;
                try {
                    const res  = await fetch('{{ route('client.checkout.validate-promo') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify({ code: this.code.toUpperCase(), plan_id: PLAN_ID }),
                    });
                    const data = await res.json();
                    if (data.valid) {
                        const newTotal = Math.max(0, PLAN_PRICE - data.discount);
                        this.applied = { label: data.label, discount: data.discount, newTotal: newTotal.toFixed(2) };
                        window._reloadStripeIntent(this.code.toUpperCase(), data.discount);
                    } else {
                        this.error = data.error;
                    }
                } catch (e) {
                    this.error = 'Une erreur est survenue.';
                }
                this.loading = false;
            },
            removeCode() {
                this.applied = null;
                this.code = '';
                this.error = '';
                window._reloadStripeIntentNoPromo();
            },
        };
    };
}());
</script>
@endpush
@endif
@endsection
