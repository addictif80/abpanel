<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('user')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('email', 'like', "%{$search}%")
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->paginate(25);

        return view('admin.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('user');
        return view('admin.invoices.show', compact('invoice'));
    }

    public function create()
    {
        $clients = User::where('is_admin', false)->orderBy('last_name')->get();
        return view('admin.invoices.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'      => 'required|exists:users,id',
            'due_date'     => 'nullable|date',
            'items'        => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $items = collect($request->items)->map(fn($item) => [
            'description' => $item['description'],
            'quantity'    => (float) $item['quantity'],
            'unit_price'  => (float) $item['unit_price'],
            'total'       => round((float) $item['quantity'] * (float) $item['unit_price'], 2),
        ])->all();

        $total = collect($items)->sum('total');

        Invoice::create([
            'user_id'  => $request->user_id,
            'number'   => Invoice::generateNumber(),
            'status'   => 'pending',
            'items'    => $items,
            'subtotal' => $total,
            'total'    => $total,
            'currency' => 'EUR',
            'due_at'   => $request->due_date ?: null,
        ]);

        return redirect()->route('admin.invoices.index')->with('success', 'Facture créée.');
    }

    public function markPaid(Invoice $invoice)
    {
        $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        return back()->with('success', 'Facture marquée comme payée.');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('admin.invoices.index')->with('success', 'Facture supprimée.');
    }
}
