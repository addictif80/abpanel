<?php

use App\Models\MailTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        MailTemplate::firstOrCreate(['key' => 'invoice_recurring'], [
            'key'          => 'invoice_recurring',
            'name'         => 'Nouvelle facture récurrente',
            'subject'      => 'Votre facture {{ invoice_number }} est disponible',
            'html_content' => '<p>Bonjour {{ client_name }},</p><p>Dans le cadre de votre abonnement <strong>{{ recurrence_label }}</strong>, votre nouvelle facture <strong>{{ invoice_number }}</strong> d\'un montant de <strong>{{ invoice_total }}</strong> a été générée.</p><p>Date d\'échéance : <strong>{{ invoice_due_at }}</strong>.</p><p>Vous pouvez la consulter et la régler depuis votre espace client.</p><p>Cordialement,<br>{{ company_name }}</p>',
            'is_active'    => true,
        ]);
    }

    public function down(): void
    {
        MailTemplate::where('key', 'invoice_recurring')->delete();
    }
};
