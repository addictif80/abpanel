<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\PdfService;

class BillingController extends Controller
{
    public function __construct(private readonly PdfService $pdfService) {}

    public function index()
    {
        $invoices      = auth()->user()->invoices()->latest()->paginate(15);
        $totalPaid     = auth()->user()->invoices()->where('status', 'paid')->sum('total');
        $pendingAmount = auth()->user()->invoices()->where('status', 'pending')->sum('total');

        return view('client.billing.index', compact('invoices', 'totalPaid', 'pendingAmount'));
    }

    public function show(Invoice $invoice)
    {
        if ($invoice->user_id !== auth()->id()) {
            abort(403);
        }

        $invoice->load('quote');

        return view('client.billing.show', compact('invoice'));
    }

    public function download(Invoice $invoice)
    {
        if ($invoice->user_id !== auth()->id()) {
            abort(403);
        }

        $pdf = $this->pdfService->generateInvoicePdf($invoice);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $invoice->number . '.pdf"',
        ]);
    }

    public function downloadXml(Invoice $invoice)
    {
        if ($invoice->user_id !== auth()->id()) {
            abort(403);
        }

        $xml = $this->pdfService->generateFacturXXml($invoice);

        return response($xml, 200, [
            'Content-Type'        => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $invoice->number . '-facturx.xml"',
        ]);
    }
}
