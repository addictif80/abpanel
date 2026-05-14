<?php

use App\Models\MailTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $base = static function (string $content): string {
            return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f4f4f5;font-family:sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:40px 20px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">
<tr><td style="background:#4f46e5;padding:30px 40px;">
<h1 style="margin:0;color:#fff;font-size:22px;font-weight:700;">{{app_name}}</h1></td></tr>
<tr><td style="padding:40px;">' . $content . '</td></tr>
<tr><td style="padding:20px 40px;background:#f9fafb;border-top:1px solid #e5e7eb;">
<p style="margin:0;font-size:12px;color:#6b7280;">Vous recevez cet email car vous êtes client {{app_name}}.</p>
</td></tr></table></td></tr></table></body></html>';
        };

        MailTemplate::firstOrCreate(['key' => 'quote_message_to_client'], [
            'name'         => 'Réponse admin sur un devis',
            'subject'      => 'Nouveau message sur votre devis {{quote_number}}',
            'html_content' => $base('
<h2 style="margin:0 0 8px;color:#111827;font-size:20px;">Nouveau message sur votre devis</h2>
<p style="margin:0 0 16px;color:#6b7280;">Bonjour {{client_name}}, notre équipe vous a laissé un message concernant le devis <strong>{{quote_number}}</strong>.</p>
<div style="background:#f9fafb;border-left:4px solid #4f46e5;border-radius:4px;padding:16px;margin-bottom:24px;">
  <p style="margin:0;color:#374151;font-size:14px;white-space:pre-line;">{{message_body}}</p>
</div>
<a href="{{quote_url}}" style="display:inline-block;padding:12px 24px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">
  Voir le devis et répondre →
</a>'),
            'is_active'    => true,
        ]);

        MailTemplate::firstOrCreate(['key' => 'quote_message_to_admin'], [
            'name'         => 'Message client sur un devis',
            'subject'      => '[Devis {{quote_number}}] Nouveau message de {{client_name}}',
            'html_content' => $base('
<h2 style="margin:0 0 8px;color:#111827;font-size:20px;">Message client sur un devis</h2>
<p style="margin:0 0 16px;color:#6b7280;">Le client <strong>{{client_name}}</strong> ({{client_email}}) a laissé un message sur le devis <strong>{{quote_number}}</strong>.</p>
<div style="background:#f9fafb;border-left:4px solid #10b981;border-radius:4px;padding:16px;margin-bottom:24px;">
  <p style="margin:0;color:#374151;font-size:14px;white-space:pre-line;">{{message_body}}</p>
</div>
<a href="{{quote_admin_url}}" style="display:inline-block;padding:12px 24px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">
  Voir le devis →
</a>'),
            'is_active'    => true,
        ]);
    }

    public function down(): void
    {
        \App\Models\MailTemplate::whereIn('key', ['quote_message_to_client', 'quote_message_to_admin'])->delete();
    }
};
