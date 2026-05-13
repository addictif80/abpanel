<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\PdfService;
use App\Services\QuoteService;

class PublicQuoteController extends Controller
{
    public function __construct(
        private readonly QuoteService $quoteService,
        private readonly PdfService $pdfService,
    ) {}

    public function show(string $token)
    {
        $quote = Quote::where('access_token', $token)
            ->where('is_template', false)
            ->with('items.product', 'user')
            ->firstOrFail();

        $this->quoteService->markViewed($quote);

        return view('quotes.public', compact('quote'));
    }

    public function accept(string $token)
    {
        $quote = Quote::where('access_token', $token)
            ->where('is_template', false)
            ->firstOrFail();

        if (! $quote->isPending()) {
            return redirect()->route('quotes.public', $token)
                ->with('error', 'Ce devis ne peut plus être accepté.');
        }

        if ($quote->isExpired()) {
            return redirect()->route('quotes.public', $token)
                ->with('error', 'Ce devis est expiré. Veuillez nous contacter pour obtenir un nouveau devis.');
        }

        $invoice = $this->quoteService->accept($quote);

        return redirect()->route('quotes.public', $token)
            ->with('success', 'Devis accepté ! Une facture vous sera transmise prochainement.');
    }

    public function refuse(string $token)
    {
        $quote = Quote::where('access_token', $token)
            ->where('is_template', false)
            ->firstOrFail();

        if (! $quote->isPending()) {
            return redirect()->route('quotes.public', $token)
                ->with('error', 'Ce devis ne peut plus être refusé.');
        }

        $this->quoteService->refuse($quote);

        return redirect()->route('quotes.public', $token)
            ->with('info', 'Devis refusé. N\'hésitez pas à nous contacter si vous souhaitez discuter d\'une nouvelle proposition.');
    }

    public function downloadPdf(string $token)
    {
        $quote = Quote::where('access_token', $token)
            ->where('is_template', false)
            ->firstOrFail();

        $pdf = $this->pdfService->generateQuotePdf($quote);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $quote->number . '.pdf"',
        ]);
    }
}
