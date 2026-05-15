<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $wrap = fn(string $body) => '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f4f4f5;font-family:sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:40px 20px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">
<tr><td style="background:#4f46e5;padding:30px 40px;"><h1 style="margin:0;color:#fff;font-size:22px;font-weight:700;">ABPanel</h1></td></tr>
<tr><td style="padding:40px;">' . $body . '</td></tr>
<tr><td style="padding:20px 40px;background:#f9fafb;border-top:1px solid #e5e7eb;">
<p style="margin:0;font-size:12px;color:#6b7280;">Vous recevez cet email car vous êtes client ABPanel.</p>
</td></tr></table></td></tr></table></body></html>';

        DB::table('mail_templates')->insert([
            [
                'key'          => 'domain_ssl_enabled',
                'name'         => 'SSL activé sur un domaine',
                'subject'      => 'SSL activé — {{domain}}',
                'html_content' => $wrap('<h2 style="margin:0 0 8px;color:#111827;font-size:20px;">SSL activé avec succès ✓</h2>
<p style="margin:0 0 20px;color:#6b7280;">Bonjour {{first_name}}, le certificat Let\'s Encrypt a été généré et activé pour votre domaine.</p>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;margin-bottom:20px;">
<tr><td style="padding:16px;">
  <p style="margin:0 0 6px;font-size:13px;color:#166534;font-weight:700;">🔒 {{domain}}</p>
  <p style="margin:0;font-size:13px;color:#166534;">Expire le : <strong>{{expires_at}}</strong></p>
</td></tr></table>
<p style="margin:0;font-size:13px;color:#6b7280;">Votre site est maintenant accessible en HTTPS. Le certificat sera renouvelé automatiquement.</p>'),
                'variables'    => json_encode(['first_name', 'domain', 'expires_at', 'app_name']),
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'key'          => 'domain_ssl_failed',
                'name'         => 'Échec activation SSL sur un domaine',
                'subject'      => 'Échec SSL — {{domain}}',
                'html_content' => $wrap('<h2 style="margin:0 0 8px;color:#111827;font-size:20px;">Échec de l\'activation SSL</h2>
<p style="margin:0 0 20px;color:#6b7280;">Bonjour {{first_name}}, la génération du certificat Let\'s Encrypt a échoué pour votre domaine <strong>{{domain}}</strong>.</p>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;margin-bottom:20px;">
<tr><td style="padding:16px;">
  <p style="margin:0 0 6px;font-size:13px;color:#991b1b;font-weight:700;">Raison probable</p>
  <p style="margin:0;font-size:13px;color:#7f1d1d;">{{error}}</p>
</td></tr></table>
<p style="margin:0;font-size:13px;color:#6b7280;">Vérifiez que votre domaine pointe bien vers l\'IP de notre serveur et réessayez depuis votre espace client.</p>'),
                'variables'    => json_encode(['first_name', 'domain', 'error', 'app_name']),
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('mail_templates')->whereIn('key', ['domain_ssl_enabled', 'domain_ssl_failed'])->delete();
    }
};
