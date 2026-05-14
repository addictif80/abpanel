<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\QuoteMessage;
use App\Models\Setting;
use App\Models\User;
use App\Services\MailService;
use App\Services\PdfService;
use App\Services\QuoteService;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function __construct(
        private readonly QuoteService $quoteService,
        private readonly PdfService $pdfService,
    ) {}

    public function index()
    {
        $quotes = auth()->user()->quotes()
            ->where('is_template', false)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->latest()
            ->paginate(15);

        return view('client.quotes.index', compact('quotes'));
    }

    public function show(Quote $quote)
    {
        if ($quote->user_id !== auth()->id()) {
            abort(403);
        }

        if (in_array($quote->status, ['draft', 'cancelled'])) {
            abort(404);
        }

        $quote->load('items.product', 'messages.user');
        $this->quoteService->markViewed($quote);

        return view('client.quotes.show', compact('quote'));
    }

    public function accept(Quote $quote)
    {
        if ($quote->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $quote->isPending()) {
            return back()->with('error', 'Ce devis ne peut plus être accepté.');
        }

        if ($quote->isExpired()) {
            return back()->with('error', 'Ce devis est expiré. Contactez-nous pour obtenir un nouveau devis.');
        }

        $invoice = $this->quoteService->accept($quote);

        return redirect()->route('client.billing.invoice', $invoice)
            ->with('success', 'Devis accepté ! Une facture a été créée. Vous pouvez maintenant procéder au règlement.');
    }

    public function refuse(Quote $quote)
    {
        if ($quote->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $quote->isPending()) {
            return back()->with('error', 'Ce devis ne peut plus être refusé.');
        }

        $this->quoteService->refuse($quote);

        return redirect()->route('client.quotes.index')
            ->with('success', 'Devis refusé. N\'hésitez pas à nous contacter si vous souhaitez le modifier.');
    }

    public function downloadPdf(Quote $quote)
    {
        if ($quote->user_id !== auth()->id()) {
            abort(403);
        }

        if (in_array($quote->status, ['draft', 'cancelled'])) {
            abort(404);
        }

        $pdf = $this->pdfService->generateQuotePdf($quote);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $quote->number . '.pdf"',
        ]);
    }

    public function addMessage(Request $request, Quote $quote)
    {
        if ($quote->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $quote->isPending()) {
            return back()->with('error', 'Vous ne pouvez laisser un message que sur un devis en attente.');
        }

        $request->validate(['body' => 'required|string|max:2000']);

        QuoteMessage::create([
            'quote_id' => $quote->id,
            'user_id'  => auth()->id(),
            'author'   => 'client',
            'body'     => $request->body,
        ]);

        // Notify admin by email
        $adminEmail = User::where('is_admin', true)->value('email');
        if ($adminEmail) {
            try {
                app(MailService::class)->sendFromTemplate('quote_message_to_admin', $adminEmail, [
                    'client_name'    => auth()->user()->full_name,
                    'client_email'   => auth()->user()->email,
                    'quote_number'   => $quote->number,
                    'message_body'   => $request->body,
                    'quote_admin_url' => route('admin.quotes.show', $quote),
                ]);
            } catch (\Exception) {}
        }

        return back()->with('success', 'Votre message a bien été envoyé.');
    }
}
