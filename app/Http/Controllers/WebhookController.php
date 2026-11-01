<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        // Lock + re-check status inside a transaction so two near-simultaneous
        // deliveries of the same event can't both pass the "not yet paid" guard.
        $invoice = DB::transaction(function () use ($paymentIntent) {
            $invoice = Invoice::where('stripe_payment_intent_id', $paymentIntent->id)
                ->lockForUpdate()
                ->first();

            if (!$invoice || $invoice->status === 'paid') {
                return null;
            }

            // Defence in depth: the amount actually captured by Stripe must match
            // what we asked for. A mismatch means the PaymentIntent was tampered
            // with (or our own amount computation is wrong) — never auto-mark paid.
            $capturedCents = (int) ($paymentIntent->amount_received ?? $paymentIntent->amount ?? 0);
            $expectedCents = (int) round(((float) $invoice->total) * 100);

            if ($capturedCents !== $expectedCents) {
                Log::error("Stripe webhook amount mismatch for invoice {$invoice->id}: captured {$capturedCents}c, expected {$expectedCents}c");
                $invoice->update(['metadata' => array_merge($invoice->metadata ?? [], [
                    'amount_mismatch' => ['captured' => $capturedCents, 'expected' => $expectedCents, 'at' => now()->toIso8601String()],
                ])]);
                return null;
            }

            $invoice->update(['status' => 'paid', 'paid_at' => now()]);

            return $invoice;
        });

        if (!$invoice) return;

        app(InvoiceFulfillmentService::class)->fulfill($invoice);
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
