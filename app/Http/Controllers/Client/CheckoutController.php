<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\PromoCode;
use App\Models\Setting;
use App\Services\InvoiceFulfillmentService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function plans()
    {
        $plans = Plan::active()->get()->groupBy('type');
        return view('client.checkout.plans', compact('plans'));
    }

    public function checkout(Plan $plan)
    {
        abort_if(!$plan->is_active, 404);

        $stripeKey  = Setting::get('stripe_public_key');
        $limitError = $plan->checkClientLimit(auth()->user());

        return view('client.checkout.checkout', compact('plan', 'stripeKey', 'limitError'));
    }

    public function validatePromo(Request $request)
    {
        $request->validate([
            'code'    => 'required|string',
            'plan_id' => 'required|integer|exists:plans,id',
        ]);

        $code = PromoCode::where('code', strtoupper($request->code))->first();
        if (!$code) return response()->json(['valid' => false, 'error' => 'Code promo invalide.']);

        $plan   = Plan::findOrFail($request->plan_id);
        $period = $plan->resolvePeriod($request->input('billing_period'));
        $result = $code->validate($plan, $plan->priceFor($period));

        return response()->json($result);
    }

    public function createIntent(Request $request, Plan $plan)
    {
        abort_if(!$plan->is_active, 404);

        // Vérification limite côté serveur (protection contre contournement JS)
        $limitError = $plan->checkClientLimit(auth()->user());
        if ($limitError) {
            return response()->json(['error' => $limitError], 403);
        }

        if ($plan->type === 'hosting') {
            $request->validate([
                'domain' => ['required', 'string', 'max:253', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9\-\.]+[a-zA-Z0-9]$/'],
            ], ['domain.regex' => 'Le domaine saisi n\'est pas valide (ex: monsite.fr).']);
        }

        $period = $plan->resolvePeriod($request->input('billing_period'));
        $price  = $plan->priceFor($period);
        $periodLabel = $period === 'yearly' ? 'Annuel' : 'Mensuel';

        $promoCode = null;
        $discount  = 0;
        if ($request->promo_code) {
            $promoCode = PromoCode::where('code', strtoupper($request->promo_code))->first();
            if ($promoCode) {
                $promoResult = $promoCode->validate($plan, $price);
                if ($promoResult['valid']) {
                    $discount = $promoResult['discount'];
                }
            }
        }

        $total = max(0, round($price - $discount, 2));

        $metadata = ['billing_period' => $period];
        if ($plan->type === 'hosting') {
            $metadata['domain'] = strtolower(trim($request->domain));
        }

        $items = [[
            'description' => $plan->name . ' — ' . $periodLabel,
            'quantity'    => 1,
            'unit_price'  => $price,
            'total'       => $price,
        ]];

        // A 100%-off promo code must actually produce a free order — Stripe
        // enforces a non-zero minimum per PaymentIntent, so forcing one through
        // Stripe anyway (clamped to 50c) would still charge the client despite
        // the "discount" they were shown. Skip Stripe entirely — but still wait
        // for the client's explicit "Payer" click (confirmFree) before actually
        // fulfilling the order: this intent can be created speculatively just
        // from applying a promo code, before the client has committed to anything.
        if ($total <= 0) {
            $invoice = Invoice::create([
                'user_id'       => auth()->id(),
                'plan_id'       => $plan->id,
                'number'        => Invoice::generateNumber(),
                'status'        => 'pending',
                'items'         => $items,
                'subtotal'      => $price,
                'tax'           => 0,
                'total'         => 0,
                'currency'      => 'EUR',
                'metadata'      => $metadata,
                'promo_code_id' => $promoCode?->id,
                'discount'      => $discount,
            ]);

            return response()->json([
                'free_order' => true,
                'invoice_id' => $invoice->id,
            ]);
        }

        try {
            $stripe   = new StripeService();
            $customer = $stripe->getOrCreateCustomer(auth()->user());

            $intent = $stripe->createPaymentIntent([
                'amount'   => (int) round($total * 100),
                'currency' => strtolower(Setting::get('default_currency', 'eur')),
                'customer' => $customer->id,
                'metadata' => [
                    'user_id'        => auth()->id(),
                    'plan_id'        => $plan->id,
                    'billing_period' => $period,
                    'promo_code'     => $promoCode?->code,
                ],
            ]);

            $invoice = Invoice::create([
                'user_id'                  => auth()->id(),
                'plan_id'                  => $plan->id,
                'number'                   => Invoice::generateNumber(),
                'status'                   => 'pending',
                'items'                    => $items,
                'subtotal'                 => $price,
                'tax'                      => 0,
                'total'                    => $total,
                'currency'                 => 'EUR',
                'metadata'                 => $metadata,
                'stripe_payment_intent_id' => $intent->id,
                'promo_code_id'            => $promoCode?->id,
                'discount'                 => $discount,
            ]);

            return response()->json([
                'client_secret' => $intent->client_secret,
                'invoice_id'    => $invoice->id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Explicit confirmation step for a 100%-off order (see createIntent()) —
     * mirrors what WebhookController::handlePaymentSucceeded does for a real
     * Stripe payment, just triggered by the client's "Payer" click instead of
     * a webhook, since there is no Stripe payment to wait for.
     */
    public function confirmFree(Request $request, Invoice $invoice)
    {
        abort_if($invoice->user_id !== auth()->id(), 403);

        $fulfilled = DB::transaction(function () use ($invoice) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->first();

            if ($invoice->status === 'paid') {
                return null; // already confirmed (double click / retry) — not an error
            }
            if ((float) $invoice->total !== 0.0) {
                abort(422, 'Cette facture n\'est pas une commande gratuite.');
            }

            $invoice->update(['status' => 'paid', 'paid_at' => now()]);
            return $invoice;
        });

        if ($fulfilled) {
            app(InvoiceFulfillmentService::class)->fulfill($fulfilled);
        }

        return response()->json(['success' => true]);
    }

    public function success(Request $request)
    {
        return view('client.checkout.success');
    }
}
