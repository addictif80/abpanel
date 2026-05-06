<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\User;
use App\Services\MailService;
use App\Services\ProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;

class WebhookController extends Controller
{
    public function stripe(Request $request)
    {
        $secret = \App\Models\Setting::get('stripe_webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                $secret
            );
        } catch (\Exception $e) {
            Log::warning('Stripe webhook invalid signature: ' . $e->getMessage());
            return response('Invalid signature', 400);
        }

        match ($event->type) {
            'payment_intent.succeeded'    => $this->handlePaymentSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    private function handlePaymentSucceeded(object $paymentIntent): void
    {
        $invoice = Invoice::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (!$invoice) return;

        $invoice->update(['status' => 'paid', 'paid_at' => now()]);

        // Provision VM/container if the plan requires it
        try {
            app(ProvisioningService::class)->provisionFromInvoice($invoice);
        } catch (\Exception $e) {
            Log::error("ProvisioningService failed for invoice {$invoice->id}: " . $e->getMessage());
        }

        try {
            app(MailService::class)->sendFromTemplate('invoice_paid', $invoice->user->email, [
                'first_name'     => $invoice->user->first_name,
                'invoice_number' => $invoice->number,
                'amount'         => number_format($invoice->total, 2) . '€',
                'date'           => now()->format('d/m/Y'),
                'invoice_url'    => route('client.billing.invoice', $invoice),
            ]);
        } catch (\Exception) {}
    }

    private function handlePaymentFailed(object $paymentIntent): void
    {
        $invoice = Invoice::where('stripe_payment_intent_id', $paymentIntent->id)->first();
        $invoice?->update(['status' => 'failed']);
    }

    private function handleSubscriptionDeleted(object $subscription): void
    {
        $user = User::where('stripe_customer_id', $subscription->customer)->first();
        if ($user) {
            Log::info("Stripe subscription deleted for user {$user->id}");
        }
    }
}
