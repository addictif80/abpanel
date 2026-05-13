<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\User;
use App\Services\PdfService;
use Illuminate\Http\Request;

class CreditNoteController extends Controller
{
    public function __construct(private readonly PdfService $pdfService) {}

    public function index(Request $request)
    {
        $query = CreditNote::with('user', 'invoice')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('email', 'like', "%{$search}%")
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]));
            });
        }

        $creditNotes = $query->paginate(25);

        return view('admin.credit-notes.index', compact('creditNotes'));
    }

    public function create(Request $request)
    {
        $clients  = User::where('is_admin', false)->orderBy('last_name')->get();
        $invoices = collect();

        $selectedClient = null;
        if ($request->filled('invoice')) {
            $selectedInvoice = Invoice::with('user')->find($request->invoice);
            $selectedClient = $selectedInvoice?->user;
            if ($selectedInvoice) {
                $invoices = Invoice::where('user_id', $selectedInvoice->user_id)
                    ->where('status', 'paid')
                    ->orderBy('number')
                    ->get();
            }
        }

        return view('admin.credit-notes.create', compact('clients', 'invoices', 'selectedClient'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount'     => 'required|numeric|min:0.01',
            'reason'     => 'nullable|string',
        ]);

        $invoice = Invoice::findOrFail($request->invoice_id);

        CreditNote::create([
            'number'     => CreditNote::generateNumber(),
            'invoice_id' => $invoice->id,
            'user_id'    => $invoice->user_id,
            'amount'     => $request->amount,
            'currency'   => $invoice->currency ?? 'EUR',
            'reason'     => $request->reason,
            'status'     => 'draft',
        ]);

        return redirect()->route('admin.credit-notes.index')->with('success', 'Avoir créé.');
    }

    public function show(CreditNote $creditNote)
    {
        $creditNote->load('user', 'invoice');
        return view('admin.credit-notes.show', compact('creditNote'));
    }

    public function issue(CreditNote $creditNote)
    {
        if ($creditNote->status !== 'draft') {
            return back()->with('error', 'Cet avoir est déjà émis.');
        }

        $creditNote->update(['status' => 'issued', 'issued_at' => now()]);

        return back()->with('success', 'Avoir émis.');
    }

    public function apply(Request $request, CreditNote $creditNote)
    {
        if ($creditNote->status !== 'issued') {
            return back()->with('error', 'L\'avoir doit être émis avant d\'être appliqué.');
        }

        $invoice = $creditNote->invoice;

        if (! $invoice) {
            return back()->with('error', 'Aucune facture associée à cet avoir.');
        }

        // Deduct credit from invoice total
        $newTotal = max(0, (float) $invoice->total - (float) $creditNote->amount);
        $updates  = ['total' => $newTotal];

        // Auto-close if fully covered
        if ($newTotal <= 0) {
            $updates['status']  = 'paid';
            $updates['paid_at'] = now();
        }

        $invoice->update($updates);
        $creditNote->update(['status' => 'applied']);

        $msg = $newTotal <= 0
            ? 'Avoir appliqué — facture soldée.'
            : 'Avoir appliqué — nouveau solde de la facture : ' . number_format($newTotal, 2) . ' ' . $invoice->currency . '.';

        return back()->with('success', $msg);
    }

    public function getInvoices(Request $request)
    {
        $invoices = Invoice::where('user_id', $request->user_id)
            ->where('status', 'paid')
            ->orderBy('number')
            ->get(['id', 'number', 'total', 'currency']);

        return response()->json($invoices);
    }
}
