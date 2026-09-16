<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;
use Stripe\Subscription;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(Setting::get('stripe_secret_key'));
    }

    public function createCustomer(User $user): Customer
    {
        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->full_name,
            'phone' => $user->phone,
            'metadata' => ['user_id' => $user->id],
        ]);

        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    public function getOrCreateCustomer(User $user): Customer
    {
        if ($user->stripe_customer_id) {
            return Customer::retrieve($user->stripe_customer_id);
        }
        return $this->createCustomer($user);
    }

    public function createPaymentIntent(array $params): PaymentIntent
    {
        return PaymentIntent::create(array_merge([
            'automatic_payment_methods' => ['enabled' => true],
        ], $params));
    }

    public function createSubscription(string $customerId, string $priceId): Subscription
    {
        return Subscription::create([
            'customer' => $customerId,
            'items' => [['price' => $priceId]],
            'payment_behavior' => 'default_incomplete',
            'expand' => ['latest_invoice.payment_intent'],
        ]);
    }

    public function cancelSubscription(string $subscriptionId): Subscription
    {
        $subscription = Subscription::retrieve($subscriptionId);
        return $subscription->cancel();
    }

    public function constructWebhookEvent(string $payload, string $sigHeader): \Stripe\Event
    {
        return \Stripe\Webhook::constructEvent(
            $payload,
            $sigHeader,
            Setting::get('stripe_webhook_secret')
        );
    }

    public function retrievePaymentIntent(string $intentId): PaymentIntent
    {
        return PaymentIntent::retrieve($intentId);
    }

    /**
     * Keep the plan's Stripe Product/Price in sync with what's configured in
     * ABPanel, so admins never have to copy IDs by hand. Stripe Prices are
     * immutable — changing amount/currency/period means creating a fresh
     * Price and archiving the old one, same as the product name/description
     * can just be updated in place.
     */
    public function syncPlan(Plan $plan): void
    {
        if ($plan->price <= 0 || ! Setting::get('stripe_secret_key')) {
            return;
        }

        $productId = $plan->stripe_product_id;

        if (! $productId) {
            $product = Product::create([
                'name'        => $plan->name,
                'description' => $plan->description ?: null,
            ]);
            $productId = $product->id;
        } else {
            Product::update($productId, [
                'name'        => $plan->name,
                'description' => $plan->description ?: null,
            ]);
        }

        $currency = strtolower($plan->currency ?: 'eur');

        $priceId = $this->syncPrice(
            $productId,
            $plan->stripe_price_id,
            (float) $plan->price,
            $currency,
            $plan->billing_period === 'yearly' ? 'year' : 'month',
        );

        $yearlyPriceId = $plan->stripe_yearly_price_id;
        if ($plan->yearly_price !== null) {
            $yearlyPriceId = $this->syncPrice(
                $productId,
                $plan->stripe_yearly_price_id,
                (float) $plan->yearly_price,
                $currency,
                'year',
            );
        }

        $plan->update([
            'stripe_product_id'      => $productId,
            'stripe_price_id'        => $priceId,
            'stripe_yearly_price_id' => $yearlyPriceId,
        ]);
    }

    /**
     * Create a fresh Price (and archive the old one, since Stripe Prices are
     * immutable) only if the amount/currency/interval actually changed.
     * Returns the Price ID to keep — either the untouched existing one or
     * the freshly created replacement.
     */
    private function syncPrice(string $productId, ?string $existingPriceId, float $amount, string $currency, string $interval): string
    {
        $wantedAmount = (int) round($amount * 100);

        $changed = true;
        if ($existingPriceId) {
            try {
                $existing = Price::retrieve($existingPriceId);
                $changed = $existing->unit_amount !== $wantedAmount
                    || $existing->currency !== $currency
                    || ($existing->recurring->interval ?? null) !== $interval;
            } catch (\Exception) {
                $changed = true;
            }
        }

        if (! $changed) {
            return $existingPriceId;
        }

        $newPrice = Price::create([
            'product'     => $productId,
            'unit_amount' => $wantedAmount,
            'currency'    => $currency,
            'recurring'   => ['interval' => $interval],
        ]);

        if ($existingPriceId) {
            try {
                Price::update($existingPriceId, ['active' => false]);
            } catch (\Exception) {}
        }

        return $newPrice->id;
    }

    public function testConnection(): bool
    {
        try {
            Customer::all(['limit' => 1]);
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
