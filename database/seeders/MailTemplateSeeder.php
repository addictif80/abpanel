<?php

namespace Database\Seeders;

use App\Models\MailTemplate;
use Illuminate\Database\Seeder;

class MailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'welcome',
                'name' => 'Bienvenue',
                'subject' => 'Bienvenue sur {{app_name}}, {{first_name}} !',
                'variables' => ['app_name', 'first_name', 'last_name', 'email', 'login_url'],
                'html_content' => $this->welcomeTemplate(),
            ],
            [
                'key' => 'invoice_paid',
                'name' => 'Facture payée',
                'subject' => 'Votre facture {{invoice_number}} a été réglée',
                'variables' => ['first_name', 'invoice_number', 'amount', 'date', 'invoice_url'],
                'html_content' => $this->invoicePaidTemplate(),
            ],
            [
                'key' => 'ticket_opened',
                'name' => 'Ticket ouvert',
                'subject' => 'Votre ticket #{{ticket_number}} a bien été créé',
                'variables' => ['first_name', 'ticket_number', 'ticket_subject', 'ticket_url'],
                'html_content' => $this->ticketOpenedTemplate(),
            ],
            [
                'key' => 'ticket_reply',
                'name' => 'Réponse à un ticket',
                'subject' => 'Nouvelle réponse sur votre ticket #{{ticket_number}}',
                'variables' => ['first_name', 'ticket_number', 'ticket_subject', 'reply_message', 'ticket_url'],
                'html_content' => $this->ticketReplyTemplate(),
            ],
            [
                'key' => 'password_reset',
                'name' => 'Réinitialisation de mot de passe',
                'subject' => 'Réinitialisation de votre mot de passe',
                'variables' => ['first_name', 'reset_url', 'expiry'],
                'html_content' => $this->passwordResetTemplate(),
            ],
        ];

        foreach ($templates as $template) {
            MailTemplate::updateOrCreate(['key' => $template['key']], $template);
        }
    }

    private function baseTemplate(string $title, string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
  .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
  .header { background: #4f46e5; padding: 32px 40px; text-align: center; }
  .header h1 { color: #fff; margin: 0; font-size: 24px; }
  .content { padding: 40px; color: #374151; line-height: 1.6; }
  .content h2 { color: #1f2937; margin-top: 0; }
  .btn { display: inline-block; padding: 12px 28px; background: #4f46e5; color: #fff !important; border-radius: 6px; text-decoration: none; font-weight: bold; margin: 16px 0; }
  .footer { background: #f9fafb; padding: 20px 40px; text-align: center; font-size: 12px; color: #9ca3af; }
</style>
</head>
<body>
<div class="container">
  <div class="header"><h1>{$title}</h1></div>
  <div class="content">{$body}</div>
  <div class="footer">© {{app_name}} — Tous droits réservés</div>
</div>
</body>
</html>
HTML;
    }

    private function welcomeTemplate(): string
    {
        $body = '<h2>Bienvenue, {{first_name}} !</h2><p>Votre compte a été créé avec succès sur {{app_name}}. Vous pouvez dès maintenant accéder à votre espace client.</p><p><a href="{{login_url}}" class="btn">Accéder à mon espace</a></p><p>Si vous avez des questions, notre équipe support est disponible via le système de tickets.</p>';
        return $this->baseTemplate('Bienvenue !', $body);
    }

    private function invoicePaidTemplate(): string
    {
        $body = '<h2>Facture réglée</h2><p>Bonjour {{first_name}},</p><p>Votre paiement de <strong>{{amount}}</strong> pour la facture <strong>{{invoice_number}}</strong> du {{date}} a bien été enregistré.</p><p><a href="{{invoice_url}}" class="btn">Télécharger la facture</a></p>';
        return $this->baseTemplate('Paiement confirmé', $body);
    }

    private function ticketOpenedTemplate(): string
    {
        $body = '<h2>Ticket créé</h2><p>Bonjour {{first_name}},</p><p>Votre ticket <strong>#{{ticket_number}}</strong> — "{{ticket_subject}}" a bien été créé. Notre équipe vous répondra dans les plus brefs délais.</p><p><a href="{{ticket_url}}" class="btn">Voir mon ticket</a></p>';
        return $this->baseTemplate('Ticket #{{ticket_number}} créé', $body);
    }

    private function ticketReplyTemplate(): string
    {
        $body = '<h2>Nouvelle réponse</h2><p>Bonjour {{first_name}},</p><p>Une réponse a été apportée à votre ticket <strong>#{{ticket_number}}</strong> — "{{ticket_subject}}" :</p><blockquote style="border-left:4px solid #4f46e5;padding:12px 16px;background:#f5f3ff;margin:16px 0;border-radius:4px;">{{reply_message}}</blockquote><p><a href="{{ticket_url}}" class="btn">Répondre</a></p>';
        return $this->baseTemplate('Réponse à votre ticket', $body);
    }

    private function passwordResetTemplate(): string
    {
        $body = '<h2>Réinitialisation du mot de passe</h2><p>Bonjour {{first_name}},</p><p>Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour en définir un nouveau. Ce lien expire dans {{expiry}}.</p><p><a href="{{reset_url}}" class="btn">Réinitialiser mon mot de passe</a></p><p>Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email.</p>';
        return $this->baseTemplate('Réinitialisation du mot de passe', $body);
    }
}
