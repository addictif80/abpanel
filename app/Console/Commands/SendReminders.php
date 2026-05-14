<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Quote;
use App\Models\QuoteLog;
use App\Models\Setting;
use App\Services\MailService;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature   = 'reminders:send {--dry-run : Afficher sans envoyer}';
    protected $description = 'Envoie les relances, expire les devis, et génère les factures récurrentes';

    public function __construct(private readonly MailService $mail)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dry     = $this->option('dry-run');
        $appName = Setting::get('company_name') ?: Setting::get('app_name', config('app.name'));
        $appUrl  = Setting::get('app_url', config('app.url'));

        // 1. Expire overdue quotes
        $expiredCount  = 0;
        $overdueQuotes = Quote::whereIn('status', ['sent', 'viewed'])
            ->where('expires_at', '<', now())
            ->get();

        foreach ($overdueQuotes as $quote) {
            $this->line("Expiration devis {$quote->number}");
            if (! $dry) {
                $quote->update(['status' => 'expired']);
                QuoteLog::create(['quote_id' => $quote->id, 'action' => 'expired', 'actor' => 'system']);
            }
            $expiredCount++;
        }

        // 2. Remind quotes expiring in 3 days
        $expiringSoon = Quote::whereIn('status', ['sent', 'viewed'])
            ->whereBetween('expires_at', [now()->addDays(2)->startOfDay(), now()->addDays(3)->endOfDay()])
            ->with('user')
            ->get();

        foreach ($expiringSoon as $quote) {
            $this->line("Relance devis {$quote->number} → {$quote->user->email}");
            if (! $dry) {
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
                QuoteLog::create(['quote_id' => $quote->id, 'action' => 'reminded', 'actor' => 'system', 'note' => 'Relance automatique J-3']);
            }
        }

        // 3. Remind overdue invoices (past due date, not paid)
        $overdueInvoices = Invoice::where('status', 'pending')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->with('user')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $this->line("Relance facture {$invoice->number} → {$invoice->user->email}");
            if (! $dry) {
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

        // 4. Generate recurring invoices
        $recurringCount = 0;
        $paymentDays    = (int) Setting::get('invoice_payment_days', 30);
        $dueInvoices    = Invoice::where('is_recurring', true)
            ->where('status', 'paid')
            ->whereNotNull('next_billing_at')
            ->where('next_billing_at', '<=', now()->toDateString())
            ->with('user')
            ->get();

        foreach ($dueInvoices as $source) {
            $this->line("Facture récurrente {$source->number} → nouvelle facture pour {$source->user->email}");

            if (! $dry) {
                $next = $this->nextBillingDate($source->next_billing_at, $source->recurrence_period);

                $newInvoice = Invoice::create([
                    'user_id'            => $source->user_id,
                    'plan_id'            => $source->plan_id,
                    'type'               => 'subscription',
                    'number'             => Invoice::generateNumber(),
                    'status'             => 'pending',
                    'items'              => $source->items,
                    'subtotal'           => $source->subtotal,
                    'tax'                => $source->tax,
                    'total'              => $source->total,
                    'currency'           => $source->currency,
                    'due_at'             => now()->addDays($paymentDays),
                    'is_recurring'       => true,
                    'recurrence_period'  => $source->recurrence_period,
                    'next_billing_at'    => $next,
                    'metadata'           => array_merge($source->metadata ?? [], ['generated_from' => $source->number]),
                ]);

                // Update parent's next billing date
                $source->update(['next_billing_at' => $next]);

                try {
                    $this->mail->sendFromTemplate('invoice_created_from_quote', $source->user->email, [
                        'client_name'    => $source->user->full_name,
                        'client_email'   => $source->user->email,
                        'invoice_number' => $newInvoice->number,
                        'invoice_total'  => number_format($newInvoice->total, 2) . ' ' . $newInvoice->currency,
                        'invoice_due_at' => $newInvoice->due_at?->format('d/m/Y') ?? '—',
                        'quote_number'   => '—',
                        'company_name'   => $appName,
                    ]);
                } catch (\Throwable) {}
            }

            $recurringCount++;
        }

        $this->info(sprintf(
            '%d devis expirés, %d relances devis, %d relances factures, %d factures récurrentes%s',
            $expiredCount,
            $expiringSoon->count(),
            $overdueInvoices->count(),
            $recurringCount,
            $dry ? ' (dry-run)' : '',
        ));

        return self::SUCCESS;
    }

    private function nextBillingDate(\Illuminate\Support\Carbon|\Carbon\Carbon $current, ?string $period): string
    {
        return match($period) {
            'quarterly' => $current->addMonths(3)->toDateString(),
            'yearly'    => $current->addYear()->toDateString(),
            default     => $current->addMonth()->toDateString(), // monthly
        };
    }
}
