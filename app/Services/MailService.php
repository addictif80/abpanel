<?php

namespace App\Services;

use App\Models\MailTemplate;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;

class MailService
{
    public function sendFromTemplate(string $templateKey, string $to, array $variables = []): void
    {
        $template = MailTemplate::where('key', $templateKey)->where('is_active', true)->first();

        if (!$template) {
            return;
        }

        $subject = $template->renderSubject($variables);
        $html = $template->render($variables);

        Mail::html($html, function ($message) use ($to, $subject) {
            $message->to($to)
                ->subject($subject)
                ->from(
                    Setting::get('mail_from_address', config('mail.from.address')),
                    Setting::get('mail_from_name', config('mail.from.name'))
                );
        });
    }
}
