<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Plan;
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

        $stripeKey = Setting::get('stripe_publishable_key');

        return view('client.checkout.checkout', compact('plan', 'stripeKey'));
    }

    public function createIntent(Request $request, Plan $plan)
    {
        abort_if(!$plan->is_active, 404);

        if ($plan->type === 'hosting') {
            $request->validate([
                'domain' => ['required', 'string', 'max:253', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9\-\.]+[a-zA-Z0-9]$/'],
            ], ['domain.regex' => 'Le domaine saisi n\'est pas valide (ex: monsite.fr).']);
        }

        $stripe = new StripeService();

        try {
            $customer = $stripe->getOrCreateCustomer(auth()->user());

            $intent = $stripe->createPaymentIntent([
                'amount'   => (int) ($plan->price * 100),
                'currency' => strtolower(Setting::get('default_currency', 'eur')),
                'customer' => $customer->id,
                'metadata' => [
                    'user_id' => auth()->id(),
                    'plan_id' => $plan->id,
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
                'total'                    => $plan->price,
                'currency'                 => 'EUR',
                'metadata'                 => $plan->type === 'hosting' ? ['domain' => strtolower(trim($request->domain))] : null,
                'stripe_payment_intent_id' => $intent->id,
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
