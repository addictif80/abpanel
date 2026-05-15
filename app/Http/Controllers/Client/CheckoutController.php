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
        $result = $code->validate($plan, $plan->price);

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

        $stripe = new StripeService();

        try {
            $customer = $stripe->getOrCreateCustomer(auth()->user());

            $promoCode = null;
            $discount  = 0;
            if ($request->promo_code) {
                $promoCode = PromoCode::where('code', strtoupper($request->promo_code))->first();
                if ($promoCode) {
                    $promoResult = $promoCode->validate($plan, $plan->price);
                    if ($promoResult['valid']) {
                        $discount = $promoResult['discount'];
                    }
                }
            }

            $finalAmount = max(50, (int)(($plan->price - $discount) * 100));

            $intent = $stripe->createPaymentIntent([
                'amount'   => $finalAmount,
                'currency' => strtolower(Setting::get('default_currency', 'eur')),
                'customer' => $customer->id,
                'metadata' => [
                    'user_id'    => auth()->id(),
                    'plan_id'    => $plan->id,
                    'promo_code' => $promoCode?->code,
                ],
            ]);

            $invoice = Invoice::create([
                'user_id'                  => auth()->id(),
                'plan_id'                  => $plan->id,
                'number'                   => Invoice::generateNumber(),
                'status'                   => 'pending',
                'items'                    => [[
                    'description' => $plan->name . ' — ' . ($plan->billing_period === 'yearly' ? 'Annuel' : 'Mensuel'),
                    'quantity'    => 1,
                    'unit_price'  => $plan->price,
                    'total'       => $plan->price,
                ]],
                'subtotal'                 => $plan->price,
                'tax'                      => 0,
                'total'                    => $plan->price - $discount,
                'currency'                 => 'EUR',
                'metadata'                 => $plan->type === 'hosting' ? ['domain' => strtolower(trim($request->domain))] : null,
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
