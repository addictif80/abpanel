<?php

use App\Models\MailTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $templates = [
            [
                'key'       => 'quote_sent',
                'name'      => 'Devis envoyé au client',
                'subject'   => 'Votre devis {{ quote_number }} est disponible',
                'body'      => '<p>Bonjour {{ client_name }},</p><p>Nous avons le plaisir de vous adresser le devis <strong>{{ quote_number }}</strong> d\'un montant de <strong>{{ quote_total }}</strong>.</p><p>Objet : {{ quote_subject }}</p><p>Ce devis est valable jusqu\'au <strong>{{ quote_expires_at }}</strong>.</p><p><a href="{{ quote_url }}" style="display:inline-block;padding:10px 20px;background:#4f46e5;color:#fff;border-radius:6px;text-decoration:none;">Consulter et valider le devis</a></p><p>N\'hésitez pas à nous contacter pour toute question.</p><p>Cordialement,<br>{{ company_name }}</p>',
                'is_active' => true,
            ],
            [
                'key'       => 'quote_accepted',
                'name'      => 'Devis accepté — notification admin',
                'subject'   => '{{ client_name }} a accepté le devis {{ quote_number }}',
                'body'      => '<p>Le client <strong>{{ client_name }}</strong> ({{ client_email }}) a accepté le devis <strong>{{ quote_number }}</strong> d\'un montant de <strong>{{ quote_total }}</strong>.</p><p>Une facture a été générée automatiquement.</p>',
                'is_active' => true,
            ],
            [
                'key'       => 'quote_reminder',
                'name'      => 'Relance devis en attente',
                'subject'   => 'Rappel — Devis {{ quote_number }} en attente de validation',
                'body'      => '<p>Bonjour {{ client_name }},</p><p>Nous vous rappelons que le devis <strong>{{ quote_number }}</strong> d\'un montant de <strong>{{ quote_total }}</strong> est en attente de votre validation.</p><p>Ce devis expire le <strong>{{ quote_expires_at }}</strong>.</p><p><a href="{{ quote_url }}" style="display:inline-block;padding:10px 20px;background:#4f46e5;color:#fff;border-radius:6px;text-decoration:none;">Consulter le devis</a></p><p>Cordialement,<br>{{ company_name }}</p>',
                'is_active' => true,
            ],
            [
                'key'       => 'invoice_reminder',
                'name'      => 'Relance facture impayée',
                'subject'   => 'Rappel — Facture {{ invoice_number }} en attente de règlement',
                'body'      => '<p>Bonjour {{ client_name }},</p><p>Sauf erreur de notre part, la facture <strong>{{ invoice_number }}</strong> d\'un montant de <strong>{{ invoice_total }}</strong> est toujours en attente de règlement.</p><p>Date d\'échéance : <strong>{{ invoice_due_at }}</strong>.</p><p>Merci de procéder au règlement dans les meilleurs délais.</p><p>Cordialement,<br>{{ company_name }}</p>',
                'is_active' => true,
            ],
            [
                'key'       => 'invoice_created_from_quote',
                'name'      => 'Facture créée depuis un devis',
                'subject'   => 'Votre facture {{ invoice_number }} est disponible',
                'body'      => '<p>Bonjour {{ client_name }},</p><p>Suite à la validation de votre devis {{ quote_number }}, nous vous adressons la facture <strong>{{ invoice_number }}</strong> d\'un montant de <strong>{{ invoice_total }}</strong>.</p><p>Date d\'échéance : <strong>{{ invoice_due_at }}</strong>.</p><p>Merci de procéder au règlement avant cette date.</p><p>Cordialement,<br>{{ company_name }}</p>',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $tpl) {
            MailTemplate::firstOrCreate(['key' => $tpl['key']], $tpl);
        }
    }

    public function down(): void
    {
        MailTemplate::whereIn('key', [
            'quote_sent', 'quote_accepted', 'quote_reminder',
            'invoice_reminder', 'invoice_created_from_quote',
        ])->delete();
    }
};
