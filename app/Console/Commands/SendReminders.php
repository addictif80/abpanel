<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Quote;
use App\Services\MailService;
use App\Models\Setting;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature   = 'reminders:send {--dry-run : Afficher sans envoyer}';
    protected $description = 'Envoie les relances pour les devis et factures en attente, et expire les devis';

    public function __construct(private readonly MailService $mail)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dry = $this->option('dry-run');
        $appName = Setting::get('app_name', config('app.name'));

        // Expire overdue quotes
        $expiredCount = 0;
        $overdueQuotes = Quote::whereIn('status', ['sent', 'viewed'])
            ->where('expires_at', '<', now())
            ->get();

        foreach ($overdueQuotes as $quote) {
            $this->line("Expiration devis {$quote->number}");
            if (!$dry) {
                $quote->update(['status' => 'expired']);
            }
            $expiredCount++;
        }

        // Remind quotes expiring in 3 days
        $expiringSoon = Quote::whereIn('status', ['sent', 'viewed'])
            ->whereBetween('expires_at', [now()->addDays(2)->startOfDay(), now()->addDays(3)->endOfDay()])
            ->with('user')
            ->get();

        foreach ($expiringSoon as $quote) {
            $this->line("Relance devis {$quote->number} → {$quote->user->email}");
            if (!$dry) {
                $appUrl = Setting::get('app_url', config('app.url'));
                $this->mail->sendFromTemplate('quote_reminder', $quote->user->email, [
                    'client_name'     => $quote->user->full_name,
                    'client_email'    => $quote->user->email,
                    'quote_number'    => $quote->number,
                    'quote_subject'   => $quote->subject ?? '—',
                    'quote_total'     => number_format($quote->total, 2) . ' ' . $quote->currency,
                    'quote_expires_at'=> $quote->expires_at?->format('d/m/Y') ?? '—',
                    'quote_url'       => rtrim($appUrl, '/') . '/quotes/' . $quote->access_token,
                    'company_name'    => $appName,
                ]);
            }
        }

        // Remind overdue invoices (past due date, not paid)
        $paymentDays = (int) Setting::get('invoice_payment_days', 30);
        $overdueInvoices = Invoice::where('status', 'pending')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->with('user')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $this->line("Relance facture {$invoice->number} → {$invoice->user->email}");
            if (!$dry) {
                $this->mail->sendFromTemplate('invoice_reminder', $invoice->user->email, [
                    'client_name'    => $invoice->user->full_name,
                    'client_email'   => $invoice->user->email,
                    'invoice_number' => $invoice->number,
                    'invoice_total'  => number_format($invoice->total, 2) . ' ' . ($invoice->currency ?? 'EUR'),
                    'invoice_due_at' => $invoice->due_at?->format('d/m/Y') ?? '—',
                    'company_name'   => $appName,
                ]);
            }
        }

        $this->info(sprintf(
            '%d devis expirés, %d relances devis, %d relances factures%s',
            $expiredCount,
            $expiringSoon->count(),
            $overdueInvoices->count(),
            $dry ? ' (dry-run)' : '',
        ));

        return self::SUCCESS;
    }
}
