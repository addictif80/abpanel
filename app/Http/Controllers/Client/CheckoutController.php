<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\PromoCode;
use App\Models\Setting;
use App\Services\StripeService;
use Illuminate\Http\Request;

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
        $period = $this->resolvePeriod($plan, $request);
        $result = $code->validate($plan, $plan->priceFor($period));

        return response()->json($result);
    }

    /** 'yearly' only if the plan actually offers it and the client asked for it; the plan's own billing_period otherwise. */
    private function resolvePeriod(Plan $plan, Request $request): string
    {
        if ($plan->hasYearlyOption()) {
            return $request->input('billing_period') === 'yearly' ? 'yearly' : 'monthly';
        }

        return $plan->billing_period === 'yearly' ? 'yearly' : 'monthly';
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

        $stripe = new StripeService();
        $period = $this->resolvePeriod($plan, $request);
        $price  = $plan->priceFor($period);
        $periodLabel = $period === 'yearly' ? 'Annuel' : 'Mensuel';

        try {
            $customer = $stripe->getOrCreateCustomer(auth()->user());

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

            $finalAmount = max(50, (int)(($price - $discount) * 100));

            $intent = $stripe->createPaymentIntent([
                'amount'   => $finalAmount,
                'currency' => strtolower(Setting::get('default_currency', 'eur')),
                'customer' => $customer->id,
                'metadata' => [
                    'user_id'        => auth()->id(),
                    'plan_id'        => $plan->id,
                    'billing_period' => $period,
                    'promo_code'     => $promoCode?->code,
                ],
            ]);

            $metadata = ['billing_period' => $period];
            if ($plan->type === 'hosting') {
                $metadata['domain'] = strtolower(trim($request->domain));
            }

            $invoice = Invoice::create([
                'user_id'                  => auth()->id(),
                'plan_id'                  => $plan->id,
                'number'                   => Invoice::generateNumber(),
                'status'                   => 'pending',
                'items'                    => [[
                    'description' => $plan->name . ' — ' . $periodLabel,
                    'quantity'    => 1,
                    'unit_price'  => $price,
                    'total'       => $price,
                ]],
                'subtotal'                 => $price,
                'tax'                      => 0,
                'total'                    => $price - $discount,
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

    public function success(Request $request)
    {
        return view('client.checkout.success');
    }
}
