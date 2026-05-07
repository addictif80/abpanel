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
            'key'          => 'vm_provisioned',
            'name'         => 'VM/Container provisionné',
            'subject'      => 'Votre {{type}} est prêt(e) — {{vm_name}}',
            'html_content' => $base('
<h2 style="margin:0 0 8px;color:#111827;font-size:20px;">Votre {{type}} est prêt(e) !</h2>
<p style="margin:0 0 24px;color:#6b7280;">Bonjour {{first_name}}, votre serveur vient d\'être déployé automatiquement. Voici vos accès.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;">
<tr><td style="padding:20px;">
  <table width="100%" cellpadding="4" cellspacing="0" style="font-size:14px;">
    <tr><td style="color:#6b7280;width:40%;">Nom</td><td style="color:#111827;font-weight:600;">{{vm_name}}</td></tr>
    <tr><td style="color:#6b7280;">Type</td><td style="color:#111827;">{{type}}</td></tr>
    <tr><td style="color:#6b7280;">vCPU</td><td style="color:#111827;">{{cores}}</td></tr>
    <tr><td style="color:#6b7280;">RAM</td><td style="color:#111827;">{{memory}}</td></tr>
    <tr><td style="color:#6b7280;">Disque</td><td style="color:#111827;">{{disk}}</td></tr>
    <tr><td style="color:#6b7280;">Sous-domaine</td><td style="color:#111827;font-family:monospace;">{{subdomain}}</td></tr>
  </table>
</td></tr></table>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;margin-bottom:24px;">
<tr><td style="padding:20px;">
  <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#92400e;">🔐 Accès root</p>
  <table cellpadding="4" cellspacing="0" style="font-size:14px;">
    <tr><td style="color:#6b7280;padding-right:16px;">Utilisateur</td><td style="font-family:monospace;color:#111827;font-weight:700;">root</td></tr>
    <tr><td style="color:#6b7280;padding-right:16px;">Mot de passe</td><td style="font-family:monospace;color:#111827;font-weight:700;">{{root_password}}</td></tr>
  </table>
  <p style="margin:12px 0 0;font-size:12px;color:#92400e;">⚠️ Changez ce mot de passe dès votre première connexion depuis le panel.</p>
</td></tr></table>

<p style="margin:0 0 16px;color:#374151;font-size:14px;">Vous pouvez gérer votre serveur directement depuis votre espace client :</p>
<a href="{{panel_url}}" style="display:inline-block;padding:12px 24px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">
  Accéder au panel →
</a>'),
            'variables'    => json_encode(['first_name', 'vm_name', 'type', 'cores', 'memory', 'disk', 'subdomain', 'root_password', 'panel_url']),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('mail_templates')->where('key', 'vm_provisioned')->delete();
    }
};
