<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

/**
 * Everything that must happen once an invoice is confirmed paid — whether
 * that payment went through Stripe (WebhookController) or the order was
 * fully covered by a 100% promo code and never touched Stripe at all
 * (Client\CheckoutController). The invoice itself must already be saved
 * with status 'paid' before calling this.
 */
class InvoiceFulfillmentService
{
    public function fulfill(Invoice $invoice): void
    {
        if ($invoice->promo_code_id) {
            $invoice->promoCode?->incrementUsage();
        }

        try {
            app(NotificationService::class)->paymentConfirmed($invoice);
            app(NotificationService::class)->paymentReceived($invoice);
        } catch (\Exception) {}

        // The invoice stays "paid" either way (the client did pay, or owed
        // nothing) — a provisioning failure must stay visible, not silently
        // leave the client without the service they just ordered.
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
}
