<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\MailService;
use App\Services\PdfService;
use App\Services\StripeService;
use Illuminate\Http\Request;

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
        $stripeKey = Setting::get('stripe_publishable_key') ?: Setting::get('stripe_public_key');

        return view('client.billing.show', compact('invoice', 'stripeKey'));
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

    public function payPage(Invoice $invoice)
    {
        if ($invoice->user_id !== auth()->id()) {
            abort(403);
        }

        if ($invoice->isPaid()) {
            return redirect()->route('client.billing.invoice', $invoice)
                ->with('info', 'Cette facture est déjà réglée.');
        }

        $stripeKey = Setting::get('stripe_publishable_key') ?: Setting::get('stripe_public_key');

        if (! $stripeKey) {
            return redirect()->route('client.billing.invoice', $invoice)
                ->with('error', 'Le paiement en ligne n\'est pas disponible pour le moment.');
        }

        return view('client.billing.pay', compact('invoice', 'stripeKey'));
    }

    public function createPayIntent(Request $request, Invoice $invoice)
    {
        if ($invoice->user_id !== auth()->id()) {
            abort(403);
        }

        if ($invoice->isPaid()) {
            return response()->json(['error' => 'Facture déjà réglée.'], 422);
        }

        try {
            $stripe   = app(StripeService::class);
            $customer = $stripe->getOrCreateCustomer(auth()->user());
            $intent   = $stripe->createPaymentIntent(
                (float) $invoice->total,
                strtolower($invoice->currency ?? 'eur'),
                $customer->id
            );

            $invoice->update(['stripe_payment_intent_id' => $intent->id]);

            return response()->json(['client_secret' => $intent->client_secret]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function paySuccess(Request $request, Invoice $invoice)
    {
        if ($invoice->user_id !== auth()->id()) {
            abort(403);
        }

        if ($invoice->isPaid()) {
            return redirect()->route('client.billing.invoice', $invoice)
                ->with('success', 'Facture déjà réglée.');
        }

        // Verify payment intent status with Stripe
        try {
            $stripe = app(StripeService::class);
            $intentId = $invoice->stripe_payment_intent_id ?? $request->input('payment_intent');

            if ($intentId) {
                $intent = $stripe->retrievePaymentIntent($intentId);

                if ($intent->status === 'succeeded') {
                    $invoice->update(['status' => 'paid', 'paid_at' => now()]);

                    try {
                        app(MailService::class)->sendFromTemplate('invoice_paid', $invoice->user->email, [
                            'client_name'    => $invoice->user->full_name,
                            'invoice_number' => $invoice->number,
                            'invoice_total'  => number_format($invoice->total, 2) . ' ' . ($invoice->currency ?? 'EUR'),
                            'paid_at'        => now()->format('d/m/Y'),
                            'company_name'   => Setting::get('company_name') ?: Setting::get('app_name', config('app.name')),
                        ]);
                    } catch (\Throwable) {}

                    return redirect()->route('client.billing.invoice', $invoice)
                        ->with('success', 'Paiement reçu ! Merci, votre facture est maintenant réglée.');
                }
            }
        } catch (\Throwable $e) {
            // Fall through to error
        }

        return redirect()->route('client.billing.invoice', $invoice)
            ->with('error', 'Paiement non confirmé. Veuillez réessayer ou nous contacter.');
    }
}
