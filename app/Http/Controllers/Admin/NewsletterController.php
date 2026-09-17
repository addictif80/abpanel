<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailLog;
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
            $html = $campaign->renderHtml([
                'first_name'      => $subscriber->first_name ?? '',
                'email'           => $subscriber->email,
                'unsubscribe_url' => route('newsletter.unsubscribe', $subscriber->unsubscribe_token),
            ]);

            try {
                Mail::html($html, fn($m) => $m
                    ->to($subscriber->email)
                    ->subject($campaign->subject)
                );

                MailLog::create([
                    'template_key' => 'newsletter:' . $campaign->id,
                    'to'           => $subscriber->email,
                    'subject'      => $campaign->subject,
                    'html_content' => $html,
                    'status'       => 'sent',
                ]);
                $sent++;
            } catch (\Exception $e) {
                MailLog::create([
                    'template_key' => 'newsletter:' . $campaign->id,
                    'to'           => $subscriber->email,
                    'subject'      => $campaign->subject,
                    'html_content' => $html,
                    'status'       => 'failed',
                    'error'        => $e->getMessage(),
                ]);
                continue;
            }
        }

        $campaign->update(['status' => 'sent', 'sent_count' => $sent, 'sent_at' => now()]);

        return back()->with('success', "Campagne envoyée à {$sent} abonné(s).");
    }
}
