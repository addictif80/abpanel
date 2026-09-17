<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\PromoCode;
use App\Models\User;
use Carbon\Carbon;
use RuntimeException;

/**
 * Shared "start billing retroactively" logic used by every import screen
 * (VPS, hébergement, cloud/LDAP, domaine) — a client already has the
 * resource from before ABPanel existed, and we need to record what they
 * already paid and set up recurring billing going forward, at the plan's
 * real catalog price (with an optional promo code applied), without
 * touching the resource itself.
 */
class BillingImportService
{
    /**
     * @throws RuntimeException if the promo code is invalid for this plan/amount
     */
    public function createPaidInvoice(
        User $client,
        Plan $plan,
        ?string $requestedPeriod = null,
        ?string $promoCode = null,
        ?string $paidAt = null,
    ): Invoice {
        $period = $plan->resolvePeriod($requestedPeriod);
        $price  = $plan->priceFor($period);
        $paidAtDate = $paidAt ? Carbon::parse($paidAt) : now();

        $promo    = null;
        $discount = 0;

        if ($promoCode) {
            $promo = PromoCode::where('code', strtoupper($promoCode))->first();
            if (!$promo) {
                throw new RuntimeException('Code promo invalide.');
            }
            $result = $promo->validate($plan, $price);
            if (!$result['valid']) {
                throw new RuntimeException($result['error']);
            }
            $discount = $result['discount'];
        }

        $total = max(0, $price - $discount);
        $periodLabel = $period === 'yearly' ? 'Annuel' : 'Mensuel';

        $invoice = Invoice::create([
            'user_id'           => $client->id,
            'plan_id'           => $plan->id,
            'number'            => Invoice::generateNumber(),
            'status'            => 'paid',
            'paid_at'           => $paidAtDate,
            'items'             => [[
                'description' => $plan->name . ' — ' . $periodLabel,
                'quantity'    => 1,
                'unit_price'  => $price,
                'total'       => $price,
            ]],
            'subtotal'          => $price,
            'tax'               => 0,
            'total'             => $total,
            'currency'          => 'EUR',
            'is_recurring'      => true,
            'recurrence_period' => $period,
            'next_billing_at'   => $period === 'yearly' ? $paidAtDate->copy()->addYear() : $paidAtDate->copy()->addMonth(),
            'promo_code_id'     => $promo?->id,
            'discount'          => $discount,
            'metadata'          => ['billing_period' => $period, 'imported' => true],
        ]);

        if ($promo) {
            $promo->incrementUsage();
        }

        return $invoice;
    }
}
