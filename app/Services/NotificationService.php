<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Quote;
use App\Models\User;

class NotificationService
{
    public function notify(User $user, string $type, string $title, string $body = '', ?string $url = null): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body ?: null,
            'url'     => $url,
        ]);
    }

    public function quoteSent(Quote $quote): void
    {
        $this->notify(
            $quote->user,
            'quote_sent',
            'Nouveau devis disponible',
            "Le devis {$quote->number}" . ($quote->subject ? " — {$quote->subject}" : '') . ' est disponible.',
            route('client.quotes.show', $quote)
        );
    }

    public function quoteMessage(Quote $quote, string $snippet, bool $toAdmin): void
    {
        if ($toAdmin) {
            $admin = User::where('is_admin', true)->first();
            if ($admin) {
                $this->notify(
                    $admin,
                    'quote_message',
                    "Message de {$quote->user->full_name} sur le devis {$quote->number}",
                    $snippet,
                    route('admin.quotes.show', $quote)
                );
            }
        } else {
            $this->notify(
                $quote->user,
                'quote_message',
                "Nouveau message sur votre devis {$quote->number}",
                $snippet,
                route('client.quotes.show', $quote)
            );
        }
    }

    public function invoiceCreated(Invoice $invoice): void
    {
        $this->notify(
            $invoice->user,
            'invoice_created',
            "Nouvelle facture {$invoice->number}",
            'Un montant de ' . number_format($invoice->total, 2) . ' € est à régler.',
            route('client.billing.invoice', $invoice)
        );
    }

    public function paymentConfirmed(Invoice $invoice): void
    {
        $this->notify(
            $invoice->user,
            'payment_confirmed',
            "Paiement confirmé — {$invoice->number}",
            'Votre paiement de ' . number_format($invoice->total, 2) . ' € a été enregistré.',
            route('client.billing.invoice', $invoice)
        );
    }

    public function paymentReceived(Invoice $invoice): void
    {
        $admin = User::where('is_admin', true)->first();
        if ($admin) {
            $this->notify(
                $admin,
                'payment_received',
                "Paiement reçu de {$invoice->user->full_name}",
                "Facture {$invoice->number} — " . number_format($invoice->total, 2) . ' €',
                route('admin.invoices.show', $invoice)
            );
        }
    }

    public function invoiceOverdue(Invoice $invoice): void
    {
        $this->notify(
            $invoice->user,
            'invoice_overdue',
            "Facture {$invoice->number} en retard de paiement",
            'Cette facture de ' . number_format($invoice->total, 2) . ' € est échue. Merci de régulariser.',
            route('client.billing.invoice', $invoice)
        );
    }

    public function renewalSoon(User $user, string $type, string $name, int $days, string $url): void
    {
        $label = $type === 'vm' ? 'VM' : 'hébergement';
        $this->notify(
            $user,
            $type === 'vm' ? 'vm_renewal_soon' : 'hosting_renewal_soon',
            "Renouvellement {$label} dans {$days} jour" . ($days > 1 ? 's' : ''),
            "{$name} arrive à échéance dans {$days} jour" . ($days > 1 ? 's' : '') . '.',
            $url
        );
    }

    public function serviceCancelled(User $user, string $type, string $name): void
    {
        $label = $type === 'vm' ? 'VM' : 'hébergement';
        $this->notify(
            $user,
            'service_cancelled',
            "Service résilié : {$name}",
            "Votre {$label} « {$name} » a été résilié.",
        );
    }

    public function projectUpdate(Project $project): void
    {
        $this->notify(
            $project->user,
            'project_update',
            "Mise à jour de votre projet : {$project->title}",
            "Statut : {$project->statusLabel()}. Avancement : {$project->progressPercent()}%.",
            route('client.projects.show', $project)
        );
    }

    public function projectMessage(Project $project, string $snippet, bool $toAdmin): void
    {
        if ($toAdmin) {
            $admin = User::where('is_admin', true)->first();
            if ($admin) {
                $this->notify(
                    $admin,
                    'project_message',
                    "Message de {$project->user->full_name} sur le projet « {$project->title} »",
                    $snippet,
                    route('admin.projects.show', $project)
                );
            }
        } else {
            $this->notify(
                $project->user,
                'project_message',
                "Nouveau message sur votre projet « {$project->title} »",
                $snippet,
                route('client.projects.show', $project)
            );
        }
    }
}
