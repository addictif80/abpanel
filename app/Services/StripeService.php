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

        $wantedAmount   = (int) round($plan->price * 100);
        $wantedCurrency = strtolower($plan->currency ?: 'eur');
        $wantedInterval = $plan->billing_period === 'yearly' ? 'year' : 'month';

        $priceChanged = true;
        if ($plan->stripe_price_id) {
            try {
                $existing = Price::retrieve($plan->stripe_price_id);
                $priceChanged = $existing->unit_amount !== $wantedAmount
                    || $existing->currency !== $wantedCurrency
                    || ($existing->recurring->interval ?? null) !== $wantedInterval;
            } catch (\Exception) {
                $priceChanged = true;
            }
        }

        $priceId = $plan->stripe_price_id;

        if ($priceChanged) {
            $newPrice = Price::create([
                'product'     => $productId,
                'unit_amount' => $wantedAmount,
                'currency'    => $wantedCurrency,
                'recurring'   => ['interval' => $wantedInterval],
            ]);

            if ($priceId) {
                try {
                    Price::update($priceId, ['active' => false]);
                } catch (\Exception) {}
            }

            $priceId = $newPrice->id;
        }

        $plan->update([
            'stripe_product_id' => $productId,
            'stripe_price_id'   => $priceId,
        ]);
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
