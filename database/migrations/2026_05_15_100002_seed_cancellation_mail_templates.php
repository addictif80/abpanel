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

        DB::table('mail_templates')->insert([
            [
                'key'          => 'vm_cancellation_code',
                'name'         => 'Code de confirmation de résiliation — VM',
                'subject'      => 'Confirmation de résiliation — {{vm_name}}',
                'html_content' => $base('
<h2 style="margin:0 0 8px;color:#111827;font-size:20px;">Demande de résiliation</h2>
<p style="margin:0 0 24px;color:#6b7280;">Bonjour {{first_name}}, nous avons reçu une demande de résiliation pour votre serveur <strong style="color:#111827;">{{vm_name}}</strong>.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;">
<tr><td style="padding:24px;text-align:center;">
  <p style="margin:0 0 8px;font-size:13px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Code de confirmation</p>
  <p style="margin:0;font-size:36px;font-weight:700;color:#111827;font-family:monospace;letter-spacing:.15em;">{{code}}</p>
  <p style="margin:8px 0 0;font-size:12px;color:#6b7280;">Ce code expire le <strong>{{expires_at}}</strong> (dans 15 minutes)</p>
</td></tr></table>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;margin-bottom:24px;">
<tr><td style="padding:20px;">
  <p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#991b1b;">⚠️ Attention — action irréversible</p>
  <p style="margin:0;font-size:13px;color:#7f1d1d;">La résiliation de votre serveur entraînera la suppression définitive de toutes vos données. Cette action ne peut pas être annulée. Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email et aucune action ne sera effectuée.</p>
</td></tr></table>

<p style="margin:0;font-size:13px;color:#6b7280;">— L\'équipe {{app_name}}</p>'),
                'variables'    => json_encode(['first_name', 'vm_name', 'code', 'expires_at', 'app_name']),
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'key'          => 'hosting_cancellation_code',
                'name'         => 'Code de confirmation de résiliation — Hébergement',
                'subject'      => 'Confirmation de résiliation — {{domain}}',
                'html_content' => $base('
<h2 style="margin:0 0 8px;color:#111827;font-size:20px;">Demande de résiliation</h2>
<p style="margin:0 0 24px;color:#6b7280;">Bonjour {{first_name}}, nous avons reçu une demande de résiliation pour votre hébergement web <strong style="color:#111827;">{{domain}}</strong>.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;">
<tr><td style="padding:24px;text-align:center;">
  <p style="margin:0 0 8px;font-size:13px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Code de confirmation</p>
  <p style="margin:0;font-size:36px;font-weight:700;color:#111827;font-family:monospace;letter-spacing:.15em;">{{code}}</p>
  <p style="margin:8px 0 0;font-size:12px;color:#6b7280;">Ce code expire le <strong>{{expires_at}}</strong> (dans 15 minutes)</p>
</td></tr></table>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;margin-bottom:24px;">
<tr><td style="padding:20px;">
  <p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#991b1b;">⚠️ Attention — action irréversible</p>
  <p style="margin:0;font-size:13px;color:#7f1d1d;">La résiliation de votre hébergement entraînera la suppression définitive de tous vos fichiers, bases de données et emails associés au domaine <strong>{{domain}}</strong>. Cette action ne peut pas être annulée. Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email et aucune action ne sera effectuée.</p>
</td></tr></table>

<p style="margin:0;font-size:13px;color:#6b7280;">— L\'équipe {{app_name}}</p>'),
                'variables'    => json_encode(['first_name', 'domain', 'code', 'expires_at', 'app_name']),
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('mail_templates')
            ->whereIn('key', ['vm_cancellation_code', 'hosting_cancellation_code'])
            ->delete();
    }
};
