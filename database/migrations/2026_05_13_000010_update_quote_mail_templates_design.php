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
<h1 style="margin:0;color:#fff;font-size:22px;font-weight:700;">{{app_name}}</h1></td></tr>
<tr><td style="padding:40px;">' . $content . '</td></tr>
<tr><td style="padding:20px 40px;background:#f9fafb;border-top:1px solid #e5e7eb;">
<p style="margin:0;font-size:12px;color:#6b7280;">Vous recevez cet email car vous êtes client {{app_name}}.</p>
</td></tr></table></td></tr></table></body></html>';
        };

        $templates = [
            'quote_sent' => $base('
<h2 style="margin:0 0 8px;color:#111827;font-size:20px;">Votre devis est disponible</h2>
<p style="margin:0 0 24px;color:#6b7280;">Bonjour {{client_name}}, nous avons le plaisir de vous adresser le devis suivant.</p>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;">
<tr><td style="padding:20px;">
  <table width="100%" cellpadding="4" cellspacing="0" style="font-size:14px;">
    <tr><td style="color:#6b7280;width:40%;">Numéro de devis</td><td style="color:#111827;font-weight:600;">{{quote_number}}</td></tr>
    <tr><td style="color:#6b7280;">Objet</td><td style="color:#111827;">{{quote_subject}}</td></tr>
    <tr><td style="color:#6b7280;">Montant</td><td style="color:#111827;font-weight:600;">{{quote_total}}</td></tr>
    <tr><td style="color:#6b7280;">Valable jusqu\'au</td><td style="color:#111827;">{{quote_expires_at}}</td></tr>
  </table>
</td></tr></table>
<a href="{{quote_url}}" style="display:inline-block;padding:12px 24px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">
  Consulter et valider le devis →
</a>
<p style="margin:24px 0 0;color:#6b7280;font-size:13px;">N\'hésitez pas à nous contacter pour toute question.</p>'),

            'quote_reminder' => $base('
<h2 style="margin:0 0 8px;color:#111827;font-size:20px;">Rappel — Devis en attente</h2>
<p style="margin:0 0 24px;color:#6b7280;">Bonjour {{client_name}}, nous vous rappelons que votre devis est en attente de validation.</p>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;">
<tr><td style="padding:20px;">
  <table width="100%" cellpadding="4" cellspacing="0" style="font-size:14px;">
    <tr><td style="color:#6b7280;width:40%;">Numéro de devis</td><td style="color:#111827;font-weight:600;">{{quote_number}}</td></tr>
    <tr><td style="color:#6b7280;">Montant</td><td style="color:#111827;font-weight:600;">{{quote_total}}</td></tr>
    <tr><td style="color:#6b7280;">Expire le</td><td style="color:#dc2626;font-weight:600;">{{quote_expires_at}}</td></tr>
  </table>
</td></tr></table>
<a href="{{quote_url}}" style="display:inline-block;padding:12px 24px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">
  Consulter le devis →
</a>'),

            'invoice_reminder' => $base('
<h2 style="margin:0 0 8px;color:#111827;font-size:20px;">Rappel — Facture en attente de règlement</h2>
<p style="margin:0 0 24px;color:#6b7280;">Bonjour {{client_name}}, sauf erreur de notre part, la facture suivante est toujours en attente de règlement.</p>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;">
<tr><td style="padding:20px;">
  <table width="100%" cellpadding="4" cellspacing="0" style="font-size:14px;">
    <tr><td style="color:#6b7280;width:40%;">Numéro de facture</td><td style="color:#111827;font-weight:600;">{{invoice_number}}</td></tr>
    <tr><td style="color:#6b7280;">Montant</td><td style="color:#111827;font-weight:600;">{{invoice_total}}</td></tr>
    <tr><td style="color:#6b7280;">Échéance</td><td style="color:#dc2626;font-weight:600;">{{invoice_due_at}}</td></tr>
  </table>
</td></tr></table>
<p style="margin:0 0 16px;color:#374151;font-size:14px;">Merci de procéder au règlement dans les meilleurs délais.</p>'),

            'invoice_created_from_quote' => $base('
<h2 style="margin:0 0 8px;color:#111827;font-size:20px;">Votre facture est disponible</h2>
<p style="margin:0 0 24px;color:#6b7280;">Bonjour {{client_name}}, suite à la validation de votre devis <strong>{{quote_number}}</strong>, voici votre facture.</p>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;">
<tr><td style="padding:20px;">
  <table width="100%" cellpadding="4" cellspacing="0" style="font-size:14px;">
    <tr><td style="color:#6b7280;width:40%;">Numéro de facture</td><td style="color:#111827;font-weight:600;">{{invoice_number}}</td></tr>
    <tr><td style="color:#6b7280;">Devis d\'origine</td><td style="color:#111827;">{{quote_number}}</td></tr>
    <tr><td style="color:#6b7280;">Montant</td><td style="color:#111827;font-weight:600;">{{invoice_total}}</td></tr>
    <tr><td style="color:#6b7280;">Échéance</td><td style="color:#111827;">{{invoice_due_at}}</td></tr>
  </table>
</td></tr></table>
<p style="margin:0 0 16px;color:#374151;font-size:14px;">Merci de procéder au règlement avant la date d\'échéance.</p>'),

            'quote_accepted' => $base('
<h2 style="margin:0 0 8px;color:#111827;font-size:20px;">Devis accepté</h2>
<p style="margin:0 0 24px;color:#6b7280;">Le client <strong>{{client_name}}</strong> ({{client_email}}) a accepté le devis suivant. Une facture a été générée automatiquement.</p>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;">
<tr><td style="padding:20px;">
  <table width="100%" cellpadding="4" cellspacing="0" style="font-size:14px;">
    <tr><td style="color:#6b7280;width:40%;">Client</td><td style="color:#111827;font-weight:600;">{{client_name}}</td></tr>
    <tr><td style="color:#6b7280;">Numéro de devis</td><td style="color:#111827;font-weight:600;">{{quote_number}}</td></tr>
    <tr><td style="color:#6b7280;">Montant</td><td style="color:#111827;font-weight:600;">{{quote_total}}</td></tr>
  </table>
</td></tr></table>'),
        ];

        foreach ($templates as $key => $html) {
            DB::table('mail_templates')
                ->where('key', $key)
                ->update(['html_content' => $html, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Restaure le contenu minimaliste d'origine
        $templates = [
            'quote_sent'                 => '<p>Bonjour {{ client_name }},</p><p>Votre devis {{ quote_number }} est disponible.</p><p><a href="{{ quote_url }}">Consulter le devis</a></p>',
            'quote_reminder'             => '<p>Bonjour {{ client_name }},</p><p>Rappel : votre devis {{ quote_number }} expire le {{ quote_expires_at }}.</p>',
            'invoice_reminder'           => '<p>Bonjour {{ client_name }},</p><p>Rappel : facture {{ invoice_number }} en attente.</p>',
            'invoice_created_from_quote' => '<p>Bonjour {{ client_name }},</p><p>Votre facture {{ invoice_number }} est disponible.</p>',
            'quote_accepted'             => '<p>Le client {{ client_name }} a accepté le devis {{ quote_number }}.</p>',
        ];

        foreach ($templates as $key => $html) {
            DB::table('mail_templates')
                ->where('key', $key)
                ->update(['html_content' => $html, 'updated_at' => now()]);
        }
    }
};
