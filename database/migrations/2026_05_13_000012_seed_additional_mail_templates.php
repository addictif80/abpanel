<?php

use App\Models\MailTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $templates = [
            [
                'key'          => 'quote_refused_client',
                'name'         => 'Confirmation refus devis (client)',
                'subject'      => 'Votre réponse concernant le devis {{ quote_number }}',
                'html_content' => '<p>Bonjour {{ client_name }},</p><p>Nous avons bien pris note de votre refus concernant le devis <strong>{{ quote_number }}</strong>.</p>@if(client_comment)<p>Votre message : <em>{{ client_comment }}</em></p>@endif<p>N\'hésitez pas à nous contacter si vous souhaitez discuter d\'une nouvelle proposition adaptée à vos besoins.</p><p>Cordialement,<br>{{ company_name }}</p>',
                'is_active'    => true,
            ],
            [
                'key'          => 'invoice_paid',
                'name'         => 'Facture réglée — confirmation client',
                'subject'      => 'Votre facture {{ invoice_number }} a bien été reçue',
                'html_content' => '<p>Bonjour {{ client_name }},</p><p>Nous vous confirmons la bonne réception de votre règlement pour la facture <strong>{{ invoice_number }}</strong> d\'un montant de <strong>{{ invoice_total }}</strong>, reçu le {{ paid_at }}.</p><p>Merci pour votre confiance.</p><p>Cordialement,<br>{{ company_name }}</p>',
                'is_active'    => true,
            ],
            [
                'key'          => 'invoice_payment_link',
                'name'         => 'Lien de paiement facture',
                'subject'      => 'Règlement en ligne — Facture {{ invoice_number }}',
                'html_content' => '<p>Bonjour {{ client_name }},</p><p>Vous pouvez procéder au règlement de la facture <strong>{{ invoice_number }}</strong> d\'un montant de <strong>{{ invoice_total }}</strong> en cliquant sur le bouton ci-dessous.</p><p><a href="{{ payment_url }}" style="display:inline-block;padding:10px 20px;background:#4f46e5;color:#fff;border-radius:6px;text-decoration:none;">Payer en ligne</a></p><p>Date d\'échéance : <strong>{{ invoice_due_at }}</strong>.</p><p>Cordialement,<br>{{ company_name }}</p>',
                'is_active'    => true,
            ],
        ];

        foreach ($templates as $tpl) {
            MailTemplate::firstOrCreate(['key' => $tpl['key']], $tpl);
        }
    }

    public function down(): void
    {
        MailTemplate::whereIn('key', ['quote_refused_client', 'invoice_paid', 'invoice_payment_link'])->delete();
    }
};
