<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $templates = [
            [
                'key'          => 'hosting_provisioned',
                'name'         => 'Hébergement provisionné',
                'subject'      => 'Votre hébergement {{domain}} est prêt',
                'html_content' => $this->hostingProvisionedHtml(),
                'variables'    => json_encode(['first_name', 'domain', 'username', 'password', 'panel_url', 'app_name']),
                'is_active'    => true,
            ],
            [
                'key'          => 'domain_added',
                'name'         => 'Domaine ajouté (NPM)',
                'subject'      => 'Votre domaine {{domain}} est configuré',
                'html_content' => $this->domainAddedHtml(),
                'variables'    => json_encode(['first_name', 'domain', 'ip', 'app_name']),
                'is_active'    => true,
            ],
            [
                'key'          => 'hosting_suspended',
                'name'         => 'Hébergement suspendu',
                'subject'      => 'Votre hébergement {{domain}} a été suspendu',
                'html_content' => $this->hostingSuspendedHtml(),
                'variables'    => json_encode(['first_name', 'domain', 'reason', 'app_name']),
                'is_active'    => true,
            ],
        ];

        foreach ($templates as $template) {
            DB::table('mail_templates')->updateOrInsert(
                ['key' => $template['key']],
                array_merge($template, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        DB::table('mail_templates')->whereIn('key', [
            'hosting_provisioned',
            'domain_added',
            'hosting_suspended',
        ])->delete();
    }

    private function hostingProvisionedHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Hébergement prêt</title></head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:40px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
      <tr><td style="background:#4f46e5;padding:32px 40px;">
        <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">{{app_name}}</h1>
      </td></tr>
      <tr><td style="padding:40px;">
        <h2 style="color:#111827;font-size:20px;margin:0 0 16px;">Votre hébergement est prêt ! 🎉</h2>
        <p style="color:#374151;font-size:15px;line-height:1.6;margin:0 0 24px;">Bonjour {{first_name}},</p>
        <p style="color:#374151;font-size:15px;line-height:1.6;margin:0 0 24px;">
          Votre hébergement web pour le domaine <strong>{{domain}}</strong> a été créé avec succès.
          Voici vos informations de connexion :
        </p>
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border-radius:8px;padding:20px;margin:0 0 24px;">
          <tr><td style="padding:6px 0;">
            <span style="color:#6b7280;font-size:13px;display:block;">Domaine</span>
            <strong style="color:#111827;font-size:15px;">{{domain}}</strong>
          </td></tr>
          <tr><td style="padding:6px 0;border-top:1px solid #e5e7eb;">
            <span style="color:#6b7280;font-size:13px;display:block;">Identifiant CyberPanel</span>
            <strong style="color:#111827;font-size:15px;font-family:monospace;">{{username}}</strong>
          </td></tr>
          <tr><td style="padding:6px 0;border-top:1px solid #e5e7eb;">
            <span style="color:#6b7280;font-size:13px;display:block;">Mot de passe</span>
            <strong style="color:#111827;font-size:15px;font-family:monospace;">{{password}}</strong>
          </td></tr>
        </table>
        <p style="margin:0 0 24px;">
          <a href="{{panel_url}}" style="display:inline-block;background:#4f46e5;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:600;font-size:15px;">
            Accéder au panneau de contrôle
          </a>
        </p>
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

    private function domainAddedHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Domaine configuré</title></head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:40px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
      <tr><td style="background:#4f46e5;padding:32px 40px;">
        <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">{{app_name}}</h1>
      </td></tr>
      <tr><td style="padding:40px;">
        <h2 style="color:#111827;font-size:20px;margin:0 0 16px;">Votre domaine est configuré ✅</h2>
        <p style="color:#374151;font-size:15px;line-height:1.6;margin:0 0 24px;">Bonjour {{first_name}},</p>
        <p style="color:#374151;font-size:15px;line-height:1.6;margin:0 0 24px;">
          Le domaine <strong>{{domain}}</strong> a été ajouté et est maintenant actif sur notre infrastructure.
          Les DNS pointent vers <code style="background:#f3f4f6;padding:2px 6px;border-radius:4px;">{{ip}}</code>.
        </p>
        <p style="color:#6b7280;font-size:13px;line-height:1.6;margin:0;">
          La propagation DNS peut prendre jusqu'à 24h selon votre registrar.
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

    private function hostingSuspendedHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Hébergement suspendu</title></head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:40px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
      <tr><td style="background:#dc2626;padding:32px 40px;">
        <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">{{app_name}}</h1>
      </td></tr>
      <tr><td style="padding:40px;">
        <h2 style="color:#111827;font-size:20px;margin:0 0 16px;">Hébergement suspendu</h2>
        <p style="color:#374151;font-size:15px;line-height:1.6;margin:0 0 24px;">Bonjour {{first_name}},</p>
        <p style="color:#374151;font-size:15px;line-height:1.6;margin:0 0 24px;">
          Votre hébergement pour le domaine <strong>{{domain}}</strong> a été suspendu.
        </p>
        <p style="color:#374151;font-size:15px;line-height:1.6;margin:0 0 24px;">
          Motif : <em>{{reason}}</em>
        </p>
        <p style="color:#6b7280;font-size:13px;line-height:1.6;margin:0;">
          Contactez notre support pour régulariser votre situation.
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
