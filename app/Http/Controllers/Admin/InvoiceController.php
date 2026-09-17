<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\PromoCode;
use App\Models\Setting;
use App\Models\User;
use App\Services\MailService;
use App\Services\NotificationService;
use App\Services\PdfService;
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
        $invoice->load('user', 'promoCode');
        return view('admin.invoices.show', compact('invoice'));
    }

    public function create()
    {
        $clients = User::where('is_admin', false)->orderBy('last_name')->get();
        $plans   = Plan::orderBy('type')->orderBy('name')->get();
        return view('admin.invoices.create', compact('clients', 'plans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'            => 'required|exists:users,id',
            'plan_id'            => 'nullable|exists:plans,id',
            'due_date'           => 'nullable|date',
            'items'              => 'required|array|min:1',
            'items.*.description'=> 'required|string',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'recurrence_period'  => 'nullable|in:monthly,quarterly,yearly',
            'next_billing_at'    => 'nullable|date',
            'promo_code'         => 'nullable|string',
            'paid_at'            => 'nullable|date',
        ]);

        $items = collect($request->items)->map(fn($item) => [
            'description' => $item['description'],
            'quantity'    => (float) $item['quantity'],
            'unit_price'  => (float) $item['unit_price'],
            'total'       => round((float) $item['quantity'] * (float) $item['unit_price'], 2),
        ])->all();

        $subtotal = collect($items)->sum('total');
        $plan     = $request->plan_id ? Plan::find($request->plan_id) : null;

        $promoCode = null;
        $discount  = 0;
        if ($request->filled('promo_code')) {
            $promoCode = PromoCode::where('code', strtoupper($request->promo_code))->first();
            if (!$promoCode) {
                return back()->withErrors(['promo_code' => 'Code promo invalide.'])->withInput();
            }
            $result = $promoCode->validate($plan, $subtotal);
            if (!$result['valid']) {
                return back()->withErrors(['promo_code' => $result['error']])->withInput();
            }
            $discount = $result['discount'];
        }

        $total       = max(0, $subtotal - $discount);
        $isRecurring = $request->boolean('is_recurring');
        $markPaid    = $request->boolean('mark_paid');

        $invoice = Invoice::create([
            'user_id'           => $request->user_id,
            'plan_id'           => $plan?->id,
            'number'            => Invoice::generateNumber(),
            'status'            => $markPaid ? 'paid' : 'pending',
            'items'             => $items,
            'subtotal'          => $subtotal,
            'total'             => $total,
            'currency'          => 'EUR',
            'due_at'            => $request->due_date ?: null,
            'paid_at'           => $markPaid ? ($request->paid_at ?: now()) : null,
            'is_recurring'      => $isRecurring,
            'recurrence_period' => $isRecurring ? $request->recurrence_period : null,
            'next_billing_at'   => $isRecurring ? $request->next_billing_at : null,
            'promo_code_id'     => $promoCode?->id,
            'discount'          => $discount,
        ]);

        if ($promoCode && $markPaid) {
            $promoCode->incrementUsage();
        }

        try {
            $markPaid
                ? app(NotificationService::class)->paymentConfirmed($invoice)
                : app(NotificationService::class)->invoiceCreated($invoice);
        } catch (\Exception) {}

        if ($markPaid) {
            try {
                $invoice->load('user');
                app(MailService::class)->sendFromTemplate('invoice_paid', $invoice->user->email, [
                    'client_name'    => $invoice->user->full_name,
                    'invoice_number' => $invoice->number,
                    'invoice_total'  => number_format($invoice->total, 2) . ' ' . ($invoice->currency ?? 'EUR'),
                    'paid_at'        => $invoice->paid_at?->format('d/m/Y') ?? now()->format('d/m/Y'),
                    'company_name'   => Setting::get('company_name') ?: Setting::get('app_name', config('app.name')),
                ]);
            } catch (\Throwable) {}
        }

        return redirect()->route('admin.invoices.index')->with('success', 'Facture créée.');
    }

    public function markPaid(Invoice $invoice)
    {
        $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        try { app(NotificationService::class)->paymentConfirmed($invoice); } catch (\Exception) {}

        try {
            $invoice->load('user');
            app(MailService::class)->sendFromTemplate('invoice_paid', $invoice->user->email, [
                'client_name'    => $invoice->user->full_name,
                'invoice_number' => $invoice->number,
                'invoice_total'  => number_format($invoice->total, 2) . ' ' . ($invoice->currency ?? 'EUR'),
                'paid_at'        => now()->format('d/m/Y'),
                'company_name'   => Setting::get('company_name') ?: Setting::get('app_name', config('app.name')),
            ]);
        } catch (\Throwable) {
            // Don't block the action if mail fails
        }

        return back()->with('success', 'Facture marquée comme payée. Confirmation envoyée au client.');
    }

    public function resendMail(Invoice $invoice)
    {
        if ($invoice->status !== 'paid') {
            return back()->with('error', "Le mail de confirmation de paiement n'est envoyable que pour une facture payée.");
        }

        try {
            $invoice->load('user');
            app(MailService::class)->sendFromTemplate('invoice_paid', $invoice->user->email, [
                'client_name'    => $invoice->user->full_name,
                'invoice_number' => $invoice->number,
                'invoice_total'  => number_format($invoice->total, 2) . ' ' . ($invoice->currency ?? 'EUR'),
                'paid_at'        => $invoice->paid_at?->format('d/m/Y') ?? now()->format('d/m/Y'),
                'company_name'   => Setting::get('company_name') ?: Setting::get('app_name', config('app.name')),
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', "Échec de l'envoi : " . $e->getMessage());
        }

        return back()->with('success', 'Mail de confirmation renvoyé à ' . $invoice->user->email . '.');
    }

    public function download(Invoice $invoice)
    {
        $pdf = app(PdfService::class)->generateInvoicePdf($invoice);
        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $invoice->number . '.pdf"',
        ]);
    }

    public function downloadXml(Invoice $invoice)
    {
        $xml = app(PdfService::class)->generateFacturXXml($invoice);
        return response($xml, 200, [
            'Content-Type'        => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $invoice->number . '-facturx.xml"',
        ]);
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('admin.invoices.index')->with('success', 'Facture supprimée.');
    }
}
