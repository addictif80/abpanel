<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use Stripe\Customer;
use Stripe\PaymentIntent;
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

    public function createPaymentIntent(float $amount, string $currency = 'eur', string $customerId = null): PaymentIntent
    {
        $data = [
            'amount' => (int) ($amount * 100),
            'currency' => $currency,
            'automatic_payment_methods' => ['enabled' => true],
        ];

        if ($customerId) {
            $data['customer'] = $customerId;
        }

        return PaymentIntent::create($data);
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
