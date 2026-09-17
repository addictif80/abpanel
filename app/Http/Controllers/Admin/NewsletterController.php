<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterList;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function index()
    {
        $lists = NewsletterList::withCount(['subscribers', 'activeSubscribers'])->get();
        $campaigns = NewsletterCampaign::with('list')->latest()->limit(10)->get();
        return view('admin.newsletter.index', compact('lists', 'campaigns'));
    }

    public function send(NewsletterCampaign $campaign)
    {
        if ($campaign->status === 'sent') {
            return back()->with('error', 'Cette campagne a déjà été envoyée.');
        }

        $campaign->update(['status' => 'sending']);

        $subscribers = $campaign->list->activeSubscribers()->get();
        $sent = 0;

        foreach ($subscribers as $subscriber) {
            try {
                $html = $campaign->renderHtml([
                    'first_name'      => $subscriber->first_name ?? '',
                    'email'           => $subscriber->email,
                    'unsubscribe_url' => route('newsletter.unsubscribe', $subscriber->unsubscribe_token),
                ]);

                Mail::html($html, fn($m) => $m
                    ->to($subscriber->email)
                    ->subject($campaign->subject)
                );
                $sent++;
            } catch (\Exception) {
                continue;
            }
        }

        $campaign->update(['status' => 'sent', 'sent_count' => $sent, 'sent_at' => now()]);

        return back()->with('success', "Campagne envoyée à {$sent} abonné(s).");
    }
}
