<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $base = static function (string $content): string {
            return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f4f4f5;font-family:sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:40px 20px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">
<tr><td style="background:#4f46e5;padding:30px 40px;">
<h1 style="margin:0;color:#fff;font-size:22px;font-weight:700;">ABPanel</h1></td></tr>
<tr><td style="padding:40px;">' . $content . '</td></tr>
<tr><td style="padding:20px 40px;background:#f9fafb;border-top:1px solid #e5e7eb;">
<p style="margin:0;font-size:12px;color:#6b7280;">Vous recevez cet email car vous êtes client ABPanel.</p>
</td></tr></table></td></tr></table></body></html>';
        };

        $templates = [
            [
                'key'          => 'welcome',
                'name'         => 'Bienvenue',
                'subject'      => 'Bienvenue sur {{app_name}} !',
                'html_content' => $base('
<h2 style="margin:0 0 8px;color:#111827;font-size:20px;">Bienvenue, {{first_name}} !</h2>
<p style="margin:0 0 24px;color:#6b7280;">Votre compte a bien été créé sur {{app_name}}. Vous pouvez dès maintenant vous connecter et accéder à votre espace client.</p>
<p style="margin:0 0 8px;color:#374151;font-size:14px;"><strong>Email :</strong> {{email}}</p>
<p style="margin:0 0 24px;color:#374151;font-size:14px;">Si vous n\'avez pas créé ce compte, ignorez cet email.</p>
<a href="{{login_url}}" style="display:inline-block;padding:12px 24px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">
  Accéder à mon espace →
</a>'),
                'variables'    => json_encode(['app_name', 'first_name', 'last_name', 'email', 'login_url']),
            ],
            [
                'key'          => 'invoice_paid',
                'name'         => 'Facture payée',
                'subject'      => 'Confirmation de paiement — Facture {{invoice_number}}',
                'html_content' => $base('
<h2 style="margin:0 0 8px;color:#111827;font-size:20px;">Paiement confirmé</h2>
<p style="margin:0 0 24px;color:#6b7280;">Bonjour {{first_name}}, nous avons bien reçu votre paiement. Merci pour votre confiance.</p>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;">
<tr><td style="padding:20px;">
  <table width="100%" cellpadding="4" cellspacing="0" style="font-size:14px;">
    <tr><td style="color:#6b7280;width:40%;">Numéro de facture</td><td style="color:#111827;font-weight:600;">{{invoice_number}}</td></tr>
    <tr><td style="color:#6b7280;">Montant</td><td style="color:#111827;font-weight:600;">{{amount}}</td></tr>
    <tr><td style="color:#6b7280;">Date</td><td style="color:#111827;">{{date}}</td></tr>
  </table>
</td></tr></table>
<a href="{{invoice_url}}" style="display:inline-block;padding:12px 24px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">
  Voir ma facture →
</a>'),
                'variables'    => json_encode(['first_name', 'invoice_number', 'amount', 'date', 'invoice_url']),
            ],
            [
                'key'          => 'ticket_opened',
                'name'         => 'Ticket ouvert (confirmation client)',
                'subject'      => 'Votre ticket #{{ticket_number}} a bien été reçu',
                'html_content' => $base('
<h2 style="margin:0 0 8px;color:#111827;font-size:20px;">Ticket reçu</h2>
<p style="margin:0 0 24px;color:#6b7280;">Bonjour {{first_name}}, votre ticket a bien été enregistré. Notre équipe vous répondra dans les meilleurs délais.</p>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;">
<tr><td style="padding:20px;">
  <table width="100%" cellpadding="4" cellspacing="0" style="font-size:14px;">
    <tr><td style="color:#6b7280;width:30%;">Numéro</td><td style="color:#111827;font-weight:600;">#{{ticket_number}}</td></tr>
    <tr><td style="color:#6b7280;">Sujet</td><td style="color:#111827;">{{ticket_subject}}</td></tr>
  </table>
</td></tr></table>
<a href="{{ticket_url}}" style="display:inline-block;padding:12px 24px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">
  Suivre mon ticket →
</a>'),
                'variables'    => json_encode(['first_name', 'ticket_number', 'ticket_subject', 'ticket_url']),
            ],
            [
                'key'          => 'ticket_reply',
                'name'         => 'Réponse au ticket',
                'subject'      => 'Nouvelle réponse sur votre ticket #{{ticket_number}}',
                'html_content' => $base('
<h2 style="margin:0 0 8px;color:#111827;font-size:20px;">Nouvelle réponse</h2>
<p style="margin:0 0 16px;color:#6b7280;">Bonjour {{first_name}}, notre équipe a répondu à votre ticket <strong>#{{ticket_number}}</strong> — {{ticket_subject}}.</p>
<div style="background:#f9fafb;border-left:4px solid #4f46e5;border-radius:4px;padding:16px;margin-bottom:24px;font-size:14px;color:#374151;line-height:1.6;">
  {{reply_message}}
</div>
<a href="{{ticket_url}}" style="display:inline-block;padding:12px 24px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">
  Répondre au ticket →
</a>'),
                'variables'    => json_encode(['first_name', 'ticket_number', 'ticket_subject', 'reply_message', 'ticket_url']),
            ],
        ];

        foreach ($templates as $template) {
            DB::table('mail_templates')->updateOrInsert(
                ['key' => $template['key']],
                array_merge($template, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    public function down(): void
    {
        DB::table('mail_templates')
            ->whereIn('key', ['welcome', 'invoice_paid', 'ticket_opened', 'ticket_reply'])
            ->delete();
    }
};
