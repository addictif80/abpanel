<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\User;
use App\Services\MailService;
use App\Services\NotificationService;
use App\Services\ProvisioningService;
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

        if ($invoice->promo_code_id) {
            $invoice->promoCode?->incrementUsage();
        }

        try {
            app(NotificationService::class)->paymentConfirmed($invoice);
            app(NotificationService::class)->paymentReceived($invoice);
        } catch (\Exception) {}

        // Provision VM/container if the plan requires it. The invoice stays
        // "paid" either way (the client did pay) but a failure here must stay
        // visible — otherwise the client paid for a service that never showed up.
        try {
            app(ProvisioningService::class)->provisionFromInvoice($invoice);
        } catch (\Exception $e) {
            $reason = $e->getMessage();
            Log::error("ProvisioningService failed for invoice {$invoice->id}: {$reason}");
            $invoice->update(['metadata' => array_merge($invoice->metadata ?? [], [
                'provisioning_failed' => true,
                'provisioning_error'  => $reason,
            ])]);
            try {
                app(NotificationService::class)->provisioningFailed($invoice, $reason);
            } catch (\Exception) {}
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
