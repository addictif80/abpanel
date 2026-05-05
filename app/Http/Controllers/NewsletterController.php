<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;

class NewsletterController extends Controller
{
    public function unsubscribe(string $token)
    {
        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->firstOrFail();

        $subscriber->update([
            'status'           => 'unsubscribed',
            'unsubscribed_at'  => now(),
        ]);

        if ($subscriber->user_id) {
            // Check if unsubscribed from all lists
            $stillSubscribed = NewsletterSubscriber::where('user_id', $subscriber->user_id)
                ->where('status', 'subscribed')
                ->exists();

            if (!$stillSubscribed) {
                $subscriber->user?->update(['newsletter_subscribed' => false]);
            }
        }

        return view('newsletter.unsubscribed');
    }
}
