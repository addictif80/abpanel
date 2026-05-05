<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;

class BillingController extends Controller
{
    public function index()
    {
        $invoices = auth()->user()->invoices()->latest()->paginate(15);
        $totalPaid = auth()->user()->invoices()->where('status', 'paid')->sum('total');
        $pendingAmount = auth()->user()->invoices()->where('status', 'pending')->sum('total');

        return view('client.billing.index', compact('invoices', 'totalPaid', 'pendingAmount'));
    }

    public function show(Invoice $invoice)
    {
        if ($invoice->user_id !== auth()->id()) {
            abort(403);
        }

        return view('client.billing.show', compact('invoice'));
    }

    public function download(Invoice $invoice)
    {
        if ($invoice->user_id !== auth()->id()) {
            abort(403);
        }

        // Simple HTML-to-print invoice — PDF generation can be added later
        return view('client.billing.print', compact('invoice'));
    }
}
