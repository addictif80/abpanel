<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('mail_templates')->updateOrInsert(
            ['key' => 'ldap_provisioned'],
            [
                'name'         => 'Compte annuaire (LDAP) créé',
                'subject'      => 'Votre compte {{app_name}} est prêt',
                'html_content' => $this->html(),
                'variables'    => json_encode(['first_name', 'username', 'password', 'group', 'app_name']),
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('mail_templates')->where('key', 'ldap_provisioned')->delete();
    }

    private function html(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Compte annuaire créé</title></head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:40px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
      <tr><td style="background:#4f46e5;padding:32px 40px;">
        <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">{{app_name}}</h1>
      </td></tr>
      <tr><td style="padding:40px;">
        <h2 style="color:#111827;font-size:20px;margin:0 0 16px;">Votre compte est prêt 🎉</h2>
        <p style="color:#374151;font-size:15px;line-height:1.6;margin:0 0 24px;">Bonjour {{first_name}},</p>
        <p style="color:#374151;font-size:15px;line-height:1.6;margin:0 0 24px;">
          Votre accès a été créé dans le groupe <strong>{{group}}</strong>. Voici vos identifiants :
        </p>
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border-radius:8px;padding:20px;margin:0 0 24px;">
          <tr><td style="padding:6px 0;">
            <span style="color:#6b7280;font-size:13px;display:block;">Identifiant</span>
            <strong style="color:#111827;font-size:15px;font-family:monospace;">{{username}}</strong>
          </td></tr>
          <tr><td style="padding:6px 0;border-top:1px solid #e5e7eb;">
            <span style="color:#6b7280;font-size:13px;display:block;">Mot de passe</span>
            <strong style="color:#111827;font-size:15px;font-family:monospace;">{{password}}</strong>
          </td></tr>
        </table>
        <p style="color:#6b7280;font-size:13px;line-height:1.6;margin:0;">
          Conservez ces informations précieusement. Si vous avez des questions, contactez notre support.
        </p>
      </td></tr>
      <tr><td style="background:#f9fafb;padding:20px 40px;border-top:1px solid #e5e7eb;">
        <p style="color:#9ca3af;font-size:12px;margin:0;text-align:center;">© {{app_name}} — Tous droits réservés</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }
};
