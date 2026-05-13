<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Setting;
use App\Models\User;
use App\Services\PdfService;
use App\Services\QuoteService;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function __construct(
        private readonly QuoteService $quoteService,
        private readonly PdfService $pdfService,
    ) {}

    public function index(Request $request)
    {
        $query = Quote::with('user')->where('is_template', false)->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('email', 'like', "%{$search}%")
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $quotes = $query->paginate(25);

        return view('admin.quotes.index', compact('quotes'));
    }

    public function create(Request $request)
    {
        $clients   = User::where('is_admin', false)->orderBy('last_name')->get();
        $products  = Product::active()->get();
        $templates = Quote::where('is_template', true)->orderBy('template_name')->get();
        $validityDays = (int) Setting::get('quote_validity_days', 30);
        $defaultNotes = Setting::get('quote_default_notes', '');

        $fromTemplate = null;
        if ($request->filled('template')) {
            $fromTemplate = Quote::where('is_template', true)->find($request->template);
        }

        return view('admin.quotes.create', compact('clients', 'products', 'templates', 'validityDays', 'defaultNotes', 'fromTemplate'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'         => 'required|exists:users,id',
            'subject'         => 'nullable|string|max:255',
            'notes'           => 'nullable|string',
            'internal_notes'  => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type'   => 'in:fixed,percent',
            'deposit_percent' => 'nullable|numeric|min:0|max:100',
            'expires_at'      => 'nullable|date',
            'items'           => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.discount_type'   => 'nullable|in:fixed,percent',
            'items.*.tax_rate'        => 'nullable|numeric|min:0|max:100',
        ]);

        $validityDays = (int) Setting::get('quote_validity_days', 30);

        $quote = Quote::create([
            'user_id'         => $request->user_id,
            'number'          => Quote::generateNumber(),
            'status'          => 'draft',
            'subject'         => $request->subject,
            'notes'           => $request->notes,
            'internal_notes'  => $request->internal_notes,
            'discount_amount' => $request->discount_amount ?? 0,
            'discount_type'   => $request->discount_type ?? 'fixed',
            'deposit_percent' => $request->deposit_percent ?? 0,
            'currency'        => Setting::get('default_currency', 'EUR'),
            'expires_at'      => $request->expires_at ?: now()->addDays($validityDays),
            'subtotal'        => 0,
            'total'           => 0,
        ]);

        foreach ($request->items as $i => $itemData) {
            $item = new QuoteItem([
                'product_id'      => $itemData['product_id'] ?? null,
                'description'     => $itemData['description'],
                'details'         => $itemData['details'] ?? null,
                'quantity'        => (float) $itemData['quantity'],
                'unit'            => $itemData['unit'] ?? 'forfait',
                'unit_price'      => (float) $itemData['unit_price'],
                'discount_amount' => (float) ($itemData['discount_amount'] ?? 0),
                'discount_type'   => $itemData['discount_type'] ?? 'fixed',
                'tax_rate'        => (float) ($itemData['tax_rate'] ?? 0),
                'sort_order'      => $i,
            ]);
            $item->total    = $item->computeTotal();
            $item->quote_id = $quote->id;
            $item->save();
        }

        $this->quoteService->recalculate($quote);
        $this->quoteService->log($quote, 'created', 'admin');

        return redirect()->route('admin.quotes.show', $quote)->with('success', 'Devis créé.');
    }

    public function show(Quote $quote)
    {
        $quote->load('items.product', 'user', 'invoices', 'logs');
        return view('admin.quotes.show', compact('quote'));
    }

    public function edit(Quote $quote)
    {
        if (! $quote->isEditable()) {
            return redirect()->route('admin.quotes.show', $quote)
                ->with('error', 'Ce devis ne peut plus être modifié dans son état actuel.');
        }

        $clients  = User::where('is_admin', false)->orderBy('last_name')->get();
        $products = Product::active()->get();
        $quote->load('items.product');

        return view('admin.quotes.edit', compact('quote', 'clients', 'products'));
    }

    public function update(Request $request, Quote $quote)
    {
        if (! $quote->isEditable()) {
            return redirect()->route('admin.quotes.show', $quote)
                ->with('error', 'Ce devis ne peut plus être modifié.');
        }

        $request->validate([
            'subject'         => 'nullable|string|max:255',
            'notes'           => 'nullable|string',
            'internal_notes'  => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type'   => 'in:fixed,percent',
            'deposit_percent' => 'nullable|numeric|min:0|max:100',
            'expires_at'      => 'nullable|date',
            'items'           => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $quote->update([
            'subject'         => $request->subject,
            'notes'           => $request->notes,
            'internal_notes'  => $request->internal_notes,
            'discount_amount' => $request->discount_amount ?? 0,
            'discount_type'   => $request->discount_type ?? 'fixed',
            'deposit_percent' => $request->deposit_percent ?? 0,
            'expires_at'      => $request->expires_at ?: $quote->expires_at,
        ]);

        $quote->items()->delete();

        foreach ($request->items as $i => $itemData) {
            $item = new QuoteItem([
                'product_id'      => $itemData['product_id'] ?? null,
                'description'     => $itemData['description'],
                'details'         => $itemData['details'] ?? null,
                'quantity'        => (float) $itemData['quantity'],
                'unit'            => $itemData['unit'] ?? 'forfait',
                'unit_price'      => (float) $itemData['unit_price'],
                'discount_amount' => (float) ($itemData['discount_amount'] ?? 0),
                'discount_type'   => $itemData['discount_type'] ?? 'fixed',
                'tax_rate'        => (float) ($itemData['tax_rate'] ?? 0),
                'sort_order'      => $i,
            ]);
            $item->total    = $item->computeTotal();
            $item->quote_id = $quote->id;
            $item->save();
        }

        $this->quoteService->recalculate($quote);

        return redirect()->route('admin.quotes.show', $quote)->with('success', 'Devis mis à jour.');
    }

    public function send(Quote $quote)
    {
        if (! in_array($quote->status, ['draft', 'sent'])) {
            return back()->with('error', 'Ce devis ne peut pas être (ré)envoyé dans son état actuel.');
        }

        $this->quoteService->send($quote);

        return back()->with('success', 'Devis envoyé au client par email.');
    }

    public function reminder(Quote $quote)
    {
        if (! $quote->isPending()) {
            return back()->with('error', 'Relance impossible : le devis n\'est pas en attente.');
        }

        $this->quoteService->sendReminder($quote);

        return back()->with('success', 'Relance envoyée au client.');
    }

    public function convert(Quote $quote)
    {
        if ($quote->status !== 'accepted') {
            return back()->with('error', 'Seul un devis accepté peut être converti en facture.');
        }

        $invoice = $this->quoteService->accept($quote);

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', 'Facture générée depuis le devis.');
    }

    public function cancel(Quote $quote)
    {
        if (in_array($quote->status, ['invoiced', 'cancelled'])) {
            return back()->with('error', 'Ce devis ne peut pas être annulé.');
        }

        $quote->update(['status' => 'cancelled']);

        return back()->with('success', 'Devis annulé.');
    }

    public function saveAsTemplate(Request $request, Quote $quote)
    {
        $request->validate(['template_name' => 'required|string|max:100']);
        $this->quoteService->duplicateAsTemplate($quote, $request->template_name);

        return back()->with('success', 'Modèle de devis créé.');
    }

    public function templates()
    {
        $templates = Quote::where('is_template', true)->with('user')->orderBy('template_name')->paginate(25);
        return view('admin.quotes.templates', compact('templates'));
    }

    public function downloadPdf(Quote $quote)
    {
        $pdf = $this->pdfService->generateQuotePdf($quote);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $quote->number . '.pdf"',
        ]);
    }

    public function destroy(Quote $quote)
    {
        if ($quote->status === 'invoiced') {
            return back()->with('error', 'Impossible de supprimer un devis déjà facturé.');
        }

        $quote->delete();

        return redirect()->route('admin.quotes.index')->with('success', 'Devis supprimé.');
    }
}
