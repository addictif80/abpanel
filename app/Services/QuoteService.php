<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\QuoteLog;
use App\Models\Setting;

class QuoteService
{
    public function __construct(
        private readonly MailService $mail,
        private readonly NotificationService $notifService,
    ) {}

    public function recalculate(Quote $quote): void
    {
        $quote->load('items');
        $quote->recomputeTotals();
        $quote->save();
    }

    public function send(Quote $quote): void
    {
        $quote->update([
            'status'  => 'sent',
            'sent_at' => now(),
        ]);

        $this->log($quote, 'sent', 'admin');
        $this->sendQuoteEmail($quote);
        $this->notifService->quoteSent($quote);
    }

    public function sendReminder(Quote $quote): void
    {
        $this->mail->sendFromTemplate('quote_reminder', $quote->user->email, $this->quoteVars($quote));
        $this->log($quote, 'reminded', 'admin');
    }

    public function markViewed(Quote $quote): void
    {
        $updates = ['last_viewed_at' => now()];
        $isFirstView = ! $quote->opened_at;

        if ($isFirstView) {
            $updates['opened_at'] = now();
        }
        if ($quote->status === 'sent') {
            $updates['status'] = 'viewed';
        }
        $quote->update($updates);

        if ($isFirstView) {
            $this->log($quote, 'viewed', 'client');
        }
    }

    public function accept(Quote $quote, ?string $comment = null, bool $cgvAccepted = false): Invoice
    {
        $extra = [];
        if ($comment) {
            $extra['client_comment'] = $comment;
        }
        if ($cgvAccepted) {
            $extra['cgv_accepted_at'] = now();
        }

        $quote->update(array_merge([
            'status'      => 'accepted',
            'accepted_at' => now(),
        ], $extra));

        $this->log($quote, 'accepted', 'client', $comment);

        $paymentDays   = (int) Setting::get('invoice_payment_days', 30);
        $dueAt         = now()->addDays($paymentDays);
        $depositAmount = $quote->depositAmount();

        if ($depositAmount > 0) {
            $depositInvoice = Invoice::create([
                'user_id'  => $quote->user_id,
                'quote_id' => $quote->id,
                'type'     => 'deposit',
                'number'   => Invoice::generateNumber(),
                'status'   => 'pending',
                'items'    => [[
                    'description' => "Acompte {$quote->deposit_percent}% — {$quote->subject}",
                    'quantity'    => 1,
                    'unit_price'  => $depositAmount,
                    'total'       => $depositAmount,
                ]],
                'subtotal' => $depositAmount,
                'tax'      => 0,
                'total'    => $depositAmount,
                'currency' => $quote->currency,
                'due_at'   => $dueAt,
            ]);

            $balanceAmount = $quote->balanceAmount();
            Invoice::create([
                'user_id'            => $quote->user_id,
                'quote_id'           => $quote->id,
                'type'               => 'balance',
                'deposit_invoice_id' => $depositInvoice->id,
                'number'             => Invoice::generateNumber(),
                'status'             => 'pending',
                'items'              => $this->quoteItemsToInvoiceItems($quote),
                'subtotal'           => $quote->subtotal,
                'tax'                => $quote->tax_amount,
                'total'              => $balanceAmount,
                'currency'           => $quote->currency,
                'due_at'             => $dueAt->copy()->addDays($paymentDays),
                'metadata'           => ['note' => "Solde après acompte de {$depositAmount} {$quote->currency}"],
            ]);

            $quote->update(['status' => 'invoiced']);
            $this->log($quote, 'converted', 'system');
            $this->notifyQuoteAccepted($quote);
            $this->sendInvoiceCreatedEmail($depositInvoice);

            return $depositInvoice;
        }

        $invoice = Invoice::create([
            'user_id'  => $quote->user_id,
            'quote_id' => $quote->id,
            'type'     => 'manual',
            'number'   => Invoice::generateNumber(),
            'status'   => 'pending',
            'items'    => $this->quoteItemsToInvoiceItems($quote),
            'subtotal' => $quote->subtotal,
            'tax'      => $quote->tax_amount,
            'total'    => $quote->total,
            'currency' => $quote->currency,
            'due_at'   => $dueAt,
            'metadata' => ['quote_number' => $quote->number, 'subject' => $quote->subject],
        ]);

        $quote->update(['status' => 'invoiced']);
        $this->log($quote, 'converted', 'system');
        $this->notifyQuoteAccepted($quote);
        $this->sendInvoiceCreatedEmail($invoice);

        return $invoice;
    }

    public function refuse(Quote $quote, ?string $comment = null): void
    {
        $extra = $comment ? ['client_comment' => $comment] : [];

        $quote->update(array_merge([
            'status'     => 'refused',
            'refused_at' => now(),
        ], $extra));

        $this->log($quote, 'refused', 'client', $comment);

        // Confirmation email to client
        $vars = $this->quoteVars($quote);
        $vars['client_comment'] = $comment ?? '';
        $this->mail->sendFromTemplate('quote_refused_client', $quote->user->email, $vars);
    }

    public function expireOverdue(): int
    {
        $quotes = Quote::whereIn('status', ['sent', 'viewed'])
            ->where('expires_at', '<', now())
            ->get();

        foreach ($quotes as $quote) {
            $quote->update(['status' => 'expired']);
            $this->log($quote, 'expired', 'system');
        }

        return $quotes->count();
    }

    public function duplicateAsTemplate(Quote $source, string $templateName): Quote
    {
        $template = $source->replicate(['access_token', 'number', 'status', 'sent_at', 'opened_at', 'last_viewed_at', 'accepted_at', 'refused_at']);
        $template->number        = Quote::generateNumber();
        $template->status        = 'draft';
        $template->is_template   = true;
        $template->template_name = $templateName;
        $template->access_token  = \Illuminate\Support\Str::random(64);
        $template->save();

        foreach ($source->items as $item) {
            $newItem = $item->replicate(['quote_id']);
            $newItem->quote_id = $template->id;
            $newItem->save();
        }

        return $template;
    }

    public function createFromTemplate(Quote $template, int $userId): Quote
    {
        $quote = $template->replicate(['access_token', 'number', 'status', 'is_template', 'template_name', 'sent_at', 'opened_at', 'last_viewed_at', 'accepted_at', 'refused_at']);
        $quote->number       = Quote::generateNumber();
        $quote->user_id      = $userId;
        $quote->status       = 'draft';
        $quote->is_template  = false;
        $quote->access_token = \Illuminate\Support\Str::random(64);

        $validityDays = (int) Setting::get('quote_validity_days', 30);
        $quote->expires_at = now()->addDays($validityDays);
        $quote->save();

        foreach ($template->items as $item) {
            $newItem = $item->replicate(['quote_id']);
            $newItem->quote_id = $quote->id;
            $newItem->save();
        }

        $this->log($quote, 'created', 'admin');

        return $quote;
    }

    public function log(Quote $quote, string $action, string $actor = 'system', ?string $note = null): void
    {
        QuoteLog::create([
            'quote_id' => $quote->id,
            'action'   => $action,
            'actor'    => $actor,
            'note'     => $note,
        ]);
    }

    private function sendQuoteEmail(Quote $quote): void
    {
        $this->mail->sendFromTemplate('quote_sent', $quote->user->email, $this->quoteVars($quote));
    }

    private function notifyQuoteAccepted(Quote $quote): void
    {
        $adminEmail = Setting::get('support_email', config('mail.from.address'));
        if ($adminEmail) {
            $this->mail->sendFromTemplate('quote_accepted', $adminEmail, $this->quoteVars($quote));
        }
    }

    private function sendInvoiceCreatedEmail(Invoice $invoice): void
    {
        $invoice->load('user', 'quote');
        $paymentDays = (int) Setting::get('invoice_payment_days', 30);

        $this->mail->sendFromTemplate('invoice_created_from_quote', $invoice->user->email, [
            'client_name'    => $invoice->user->full_name,
            'client_email'   => $invoice->user->email,
            'invoice_number' => $invoice->number,
            'invoice_total'  => number_format($invoice->total, 2) . ' ' . $invoice->currency,
            'invoice_due_at' => $invoice->due_at ? $invoice->due_at->format('d/m/Y') : '—',
            'quote_number'   => $invoice->quote?->number ?? '—',
            'company_name'   => Setting::get('company_name') ?: Setting::get('app_name', config('app.name')),
        ]);
    }

    private function quoteVars(Quote $quote): array
    {
        $appUrl = Setting::get('app_url', config('app.url'));
        return [
            'client_name'     => $quote->user->full_name,
            'client_email'    => $quote->user->email,
            'quote_number'    => $quote->number,
            'quote_subject'   => $quote->subject ?? '—',
            'quote_total'     => number_format($quote->total, 2) . ' ' . $quote->currency,
            'quote_expires_at'=> $quote->expires_at ? $quote->expires_at->format('d/m/Y') : '—',
            'quote_url'       => rtrim($appUrl, '/') . '/quotes/' . $quote->access_token,
            'company_name'    => Setting::get('company_name') ?: Setting::get('app_name', config('app.name')),
        ];
    }

    private function quoteItemsToInvoiceItems(Quote $quote): array
    {
        return $quote->items->map(fn($item) => [
            'description' => $item->description,
            'quantity'    => (float) $item->quantity,
            'unit_price'  => (float) $item->unit_price,
            'total'       => (float) $item->total,
            'amount'      => (float) $item->total,
        ])->values()->all();
    }
}
