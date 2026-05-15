<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use horstoeko\zugferd\ZugferdDocumentBuilder;
use horstoeko\zugferd\ZugferdDocumentPdfBuilder;
use horstoeko\zugferd\ZugferdProfiles;

class PdfService
{
    public function generateQuotePdf(Quote $quote): string
    {
        $quote->load('items.product', 'user');
        $settings = Setting::group('general') + Setting::group('company') + Setting::group('quotes');

        $pdf = Pdf::loadView('pdf.quote', compact('quote', 'settings'))
            ->setPaper('a4');

        return $pdf->output();
    }

    public function generateInvoicePdf(Invoice $invoice): string
    {
        $invoice->load('user', 'quote');
        $settings = Setting::group('general') + Setting::group('company') + Setting::group('quotes');

        $pdfContent = Pdf::loadView('pdf.invoice', compact('invoice', 'settings'))
            ->setPaper('a4')
            ->output();

        // Embed Factur-X XML if e-invoicing is enabled
        if (($settings['einvoicing_enabled'] ?? '0') === '1') {
            try {
                $builder = $this->buildEN16931Document($invoice, $settings);
                $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfString($builder, $pdfContent);
                $pdfBuilder->generateDocument();
                return $pdfBuilder->downloadString();
            } catch (\Throwable) {
                // Fall back to plain PDF if XML embedding fails
            }
        }

        return $pdfContent;
    }

    public function generateFacturXXml(Invoice $invoice): string
    {
        $invoice->load('user');
        $settings = Setting::group('general') + Setting::group('company') + Setting::group('quotes');

        $builder = $this->buildEN16931Document($invoice, $settings);
        return $builder->getContent();
    }

    private function buildEN16931Document(Invoice $invoice, array $settings): ZugferdDocumentBuilder
    {
        $invoice->loadMissing('user');

        $sellerName    = $settings['company_name'] ?? $settings['app_name'] ?? config('app.name');
        $sellerVat     = $settings['company_vat_number'] ?? '';
        $sellerSiren   = $settings['company_siren'] ?? '';
        $sellerAddress = $settings['company_address'] ?? '';
        $sellerPhone   = $settings['company_phone'] ?? '';
        $vatMention    = $settings['vat_mention'] ?? 'TVA non applicable, art. 293 B du CGI';
        $currency      = $invoice->currency ?? 'EUR';

        $isVatExempt   = empty($sellerVat);
        $taxRate       = $isVatExempt ? 0.0 : (float) ($settings['tax_rate'] ?? 20);

        $builder = ZugferdDocumentBuilder::createNew(ZugferdProfiles::PROFILE_EN16931);

        $builder->setDocumentInformation(
            $invoice->number,
            '380',
            $invoice->created_at->toDateTime(),
            $currency
        );

        if ($isVatExempt) {
            $builder->addDocumentNote($vatMention);
        }

        // Seller
        $builder->setDocumentSeller($sellerName);
        if ($sellerVat) {
            $builder->addDocumentSellerTaxRegistration('VA', $sellerVat);
        }
        if ($sellerSiren) {
            $builder->setDocumentSellerLegalOrganisation($sellerSiren, '0002', $sellerName);
        }
        if ($sellerAddress) {
            // Parse "12 rue X, 75001 Paris, France" style
            [$line1, $zip, $city, $country] = $this->parseAddress($sellerAddress);
            $builder->setDocumentSellerAddress($line1, null, null, $zip, $city, $country ?: 'FR');
        } else {
            $builder->setDocumentSellerAddress(null, null, null, null, null, 'FR');
        }
        if ($sellerPhone) {
            $builder->setDocumentSellerContact(null, null, $sellerPhone, null, null);
        }

        // Buyer
        $buyer = $invoice->user;
        $builder->setDocumentBuyer($buyer->full_name ?? $buyer->name ?? 'Client');
        if ($buyer->siret) {
            $builder->setDocumentBuyerLegalOrganisation($buyer->siret, '0009', $buyer->full_name);
        }
        $builder->setDocumentBuyerAddress(
            $buyer->address ?? null,
            null,
            null,
            $buyer->zip ?? null,
            $buyer->city ?? null,
            $buyer->country ?? 'FR'
        );

        // Delivery
        $builder->setDocumentSupplyChainEvent($invoice->created_at->toDateTime());

        // Payment terms / due date
        if ($invoice->due_at) {
            $builder->addDocumentPaymentTerm(null, $invoice->due_at->toDateTime());
        }

        // Tax — EN 16931 requires at least one tax entry
        $subtotal = (float) ($invoice->subtotal ?? $invoice->total ?? 0);
        $taxAmount = (float) ($invoice->tax ?? 0);

        if ($isVatExempt) {
            $builder->addDocumentTax('S', 'VAT', $subtotal, 0.0, 0.0);
        } else {
            $builder->addDocumentTax('S', 'VAT', $subtotal, $taxAmount, $taxRate);
        }

        // Line items
        $items = $invoice->items ?? [];
        $lineNumber = 1;
        foreach ($items as $item) {
            $qty       = (float) ($item['quantity'] ?? 1);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $lineTotal = round($qty * $unitPrice, 2);
            $desc      = $item['description'] ?? 'Prestation';

            $builder->addNewPosition((string) $lineNumber);
            $builder->setDocumentPositionProductDetails($desc);
            $builder->setDocumentPositionNetPrice($unitPrice);
            $builder->setDocumentPositionQuantity($qty, 'C62');
            if ($isVatExempt) {
                $builder->addDocumentPositionTax('S', 'VAT', 0.0);
            } else {
                $builder->addDocumentPositionTax('S', 'VAT', $taxRate);
            }
            $builder->setDocumentPositionLineSummation($lineTotal);

            $lineNumber++;
        }

        // Monetary summary
        $total = (float) ($invoice->total ?? 0);
        $builder->setDocumentSummation(
            $total,
            $total,
            $subtotal,
            0.0,
            0.0,
            $subtotal,
            $taxAmount
        );

        return $builder;
    }

    private function parseAddress(string $address): array
    {
        $parts = array_map('trim', explode(',', $address));
        $line1   = $parts[0] ?? '';
        $zipCity = $parts[1] ?? '';
        $country = $parts[2] ?? '';

        // Try to split zip and city: "75001 Paris"
        $zip  = '';
        $city = $zipCity;
        if (preg_match('/^(\d{4,6})\s+(.+)$/', $zipCity, $m)) {
            $zip  = $m[1];
            $city = $m[2];
        }

        return [$line1, $zip, $city, $country];
    }
}
