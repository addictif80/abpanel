<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\PdfService;
use App\Services\QuoteService;
use Illuminate\Http\Request;

class PublicQuoteController extends Controller
{
    public function __construct(
        private readonly QuoteService $quoteService,
        private readonly PdfService $pdfService,
    ) {}

    public function show(string $token)
    {
        $quote = Quote::where('access_token', $token)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->where('is_template', false)
            ->with('items.product', 'user')
            ->firstOrFail();

        $this->quoteService->markViewed($quote);

        $cgvPath = \App\Models\Setting::get('cgv_path');

        return view('quotes.public', compact('quote', 'cgvPath'));
    }

    public function accept(Request $request, string $token)
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

        $cgvPath = \App\Models\Setting::get('cgv_path');
        if ($cgvPath && ! $request->boolean('cgv_accepted')) {
            return redirect()->route('quotes.public', $token)
                ->with('error', 'Vous devez accepter les Conditions Générales de Vente pour valider le devis.');
        }

        $invoice = $this->quoteService->accept(
            $quote,
            $request->input('client_comment'),
            $request->boolean('cgv_accepted')
        );

        return redirect()->route('quotes.public', $token)
            ->with('success', 'Devis accepté ! Une facture vous sera transmise prochainement.');
    }

    public function refuse(Request $request, string $token)
    {
        $quote = Quote::where('access_token', $token)
            ->where('is_template', false)
            ->firstOrFail();

        if (! $quote->isPending()) {
            return redirect()->route('quotes.public', $token)
                ->with('error', 'Ce devis ne peut plus être refusé.');
        }

        $this->quoteService->refuse($quote, $request->input('client_comment'));

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
