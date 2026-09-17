<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterCampaign extends Model
{
    protected $fillable = [
        'list_id', 'name', 'subject', 'message', 'html_content',
        'text_content', 'status', 'sent_count', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(NewsletterList::class, 'list_id');
    }

    /**
     * Wraps subject + message in the same branded shell used by the
     * transactional mail templates (MailTemplate) — admins compose the
     * message with the rich text editor, the surrounding design lives in
     * one place. Always includes the unsubscribe link, regardless of
     * whether {{unsubscribe_url}} is referenced in $vars, so campaigns stay
     * compliant without the admin having to remember to add it.
     *
     * $this->message is trusted HTML authored by an admin through the rich
     * text editor (Quill), not user input — inserted as-is, not escaped.
     */
    public function renderHtml(array $vars = []): string
    {
        $vars += [
            'app_name' => Setting::get('company_name', config('app.name')),
            'subject'  => $this->subject,
        ];

        $template = <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{subject}}</title>
<style>
.newsletter-content p { margin: 0 0 16px; }
.newsletter-content a { color: #4f46e5; }
.newsletter-content img { max-width: 100%; height: auto; }
</style>
</head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:40px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
      <tr><td style="background:#4f46e5;padding:32px 40px;">
        <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">{{app_name}}</h1>
      </td></tr>
      <tr><td style="padding:40px;">
        <h2 style="color:#111827;font-size:20px;margin:0 0 20px;">{{subject}}</h2>
        <div class="newsletter-content" style="color:#374151;font-size:15px;line-height:1.6;">__MESSAGE_BODY__</div>
      </td></tr>
      <tr><td style="background:#f9fafb;padding:20px 40px;border-top:1px solid #e5e7eb;">
        <p style="color:#9ca3af;font-size:12px;margin:0 0 8px;text-align:center;">© {{app_name}} — Tous droits réservés</p>
        <p style="color:#9ca3af;font-size:11px;margin:0;text-align:center;"><a href="{{unsubscribe_url}}" style="color:#9ca3af;">Se désinscrire</a></p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;

        $template = str_replace('__MESSAGE_BODY__', $this->message ?? '', $template);

        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function ($m) use ($vars) {
            return $vars[$m[1]] ?? $m[0];
        }, $template);
    }
}
