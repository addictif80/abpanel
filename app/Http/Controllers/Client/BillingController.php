<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\MailService;
use App\Services\PdfService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        $invoice->load('quote', 'promoCode');
        $stripeKey = Setting::get('stripe_public_key');

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

        $stripeKey = Setting::get('stripe_public_key');

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
            Log::error("Stripe createPayIntent failed for invoice {$invoice->id}: " . $e->getMessage());
            return response()->json(['error' => "Impossible d'initier le paiement pour le moment. Veuillez réessayer ou nous contacter."], 500);
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

        // Verify payment intent status with Stripe. Only ever trust the intent ID
        // this invoice itself created (createPayIntent) — never a client-supplied
        // one, which would let a client mark ANY of their invoices paid using a
        // real but unrelated (and possibly much smaller) successful payment.
        try {
            $stripe = app(StripeService::class);
            $intentId = $invoice->stripe_payment_intent_id;

            if ($intentId) {
                $intent = $stripe->retrievePaymentIntent($intentId);

                $capturedCents = (int) ($intent->amount_received ?? $intent->amount ?? 0);
                $expectedCents = (int) round(((float) $invoice->total) * 100);

                if ($intent->status === 'succeeded' && $capturedCents === $expectedCents) {
                    $invoice->update(['status' => 'paid', 'paid_at' => now()]);

                    try {
                        app(MailService::class)->sendFromTemplate('invoice_paid', $invoice->user->email, [
                            'first_name'     => $invoice->user->first_name,
                            'invoice_number' => $invoice->number,
                            'amount'         => number_format($invoice->total, 2) . ' ' . ($invoice->currency ?? 'EUR'),
                            'date'           => now()->format('d/m/Y'),
                            'invoice_url'    => route('client.billing.invoice', $invoice),
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
