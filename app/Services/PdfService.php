<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    public function generateQuotePdf(Quote $quote): string
    {
        $quote->load('items.product', 'user');
        $settings = Setting::group('company') + Setting::group('quotes');

        $pdf = Pdf::loadView('pdf.quote', compact('quote', 'settings'))
            ->setPaper('a4');

        return $pdf->output();
    }

    public function generateInvoicePdf(Invoice $invoice): string
    {
        $invoice->load('user', 'quote');
        $settings = Setting::group('company') + Setting::group('quotes');

        $pdf = Pdf::loadView('pdf.invoice', compact('invoice', 'settings'))
            ->setPaper('a4');

        return $pdf->output();
    }

    public function generateFacturXXml(Invoice $invoice): string
    {
        $invoice->load('user');
        $settings = Setting::group('company') + Setting::group('quotes');

        $seller = [
            'name'      => $settings['app_name'] ?? config('app.name'),
            'siren'     => $settings['company_siren'] ?? '',
            'address'   => '',
            'legal_form'=> $settings['company_legal_form'] ?? 'Micro-entreprise',
        ];

        $buyer = [
            'name'    => $invoice->user->full_name,
            'address' => trim(implode(', ', array_filter([
                $invoice->user->address,
                $invoice->user->zip,
                $invoice->user->city,
                $invoice->user->country,
            ]))),
            'siret'   => $invoice->user->siret ?? '',
        ];

        $date        = $invoice->created_at->format('Ymd');
        $dueDate     = $invoice->due_at ? $invoice->due_at->format('Ymd') : $date;
        $totalAmount = number_format((float) $invoice->total, 2, '.', '');
        $taxAmount   = number_format((float) $invoice->tax, 2, '.', '');
        $vatMention  = $settings['vat_mention'] ?? 'TVA non applicable, art. 293 B du CGI';

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rsm:CrossIndustryInvoice xmlns:rsm="urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100"';
        $xml .= ' xmlns:ram="urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100"';
        $xml .= ' xmlns:udt="urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100">' . "\n";

        $xml .= "  <rsm:ExchangedDocumentContext>\n";
        $xml .= "    <ram:GuidelineSpecifiedDocumentContextParameter>\n";
        $xml .= "      <ram:ID>urn:factur-x.eu:1p0:minimum</ram:ID>\n";
        $xml .= "    </ram:GuidelineSpecifiedDocumentContextParameter>\n";
        $xml .= "  </rsm:ExchangedDocumentContext>\n";

        $xml .= "  <rsm:ExchangedDocument>\n";
        $xml .= "    <ram:ID>" . htmlspecialchars($invoice->number) . "</ram:ID>\n";
        $xml .= "    <ram:TypeCode>380</ram:TypeCode>\n";
        $xml .= "    <ram:IssueDateTime><udt:DateTimeString format=\"102\">{$date}</udt:DateTimeString></ram:IssueDateTime>\n";
        $xml .= "    <ram:IncludedNote><ram:Content>" . htmlspecialchars($vatMention) . "</ram:Content></ram:IncludedNote>\n";
        $xml .= "  </rsm:ExchangedDocument>\n";

        $xml .= "  <rsm:SupplyChainTradeTransaction>\n";
        $xml .= "    <ram:ApplicableHeaderTradeAgreement>\n";
        $xml .= "      <ram:SellerTradeParty>\n";
        $xml .= "        <ram:Name>" . htmlspecialchars($seller['name']) . "</ram:Name>\n";
        if ($seller['siren']) {
            $xml .= "        <ram:SpecifiedLegalOrganization><ram:ID schemeID=\"0002\">" . htmlspecialchars($seller['siren']) . "</ram:ID></ram:SpecifiedLegalOrganization>\n";
        }
        $xml .= "      </ram:SellerTradeParty>\n";
        $xml .= "      <ram:BuyerTradeParty>\n";
        $xml .= "        <ram:Name>" . htmlspecialchars($buyer['name']) . "</ram:Name>\n";
        if ($buyer['siret']) {
            $xml .= "        <ram:SpecifiedLegalOrganization><ram:ID schemeID=\"0009\">" . htmlspecialchars($buyer['siret']) . "</ram:ID></ram:SpecifiedLegalOrganization>\n";
        }
        $xml .= "      </ram:BuyerTradeParty>\n";
        $xml .= "    </ram:ApplicableHeaderTradeAgreement>\n";

        $xml .= "    <ram:ApplicableHeaderTradeDelivery/>\n";

        $xml .= "    <ram:ApplicableHeaderTradeSettlement>\n";
        $xml .= "      <ram:InvoiceCurrencyCode>" . htmlspecialchars($invoice->currency ?? 'EUR') . "</ram:InvoiceCurrencyCode>\n";
        $xml .= "      <ram:SpecifiedTradePaymentTerms>\n";
        $xml .= "        <ram:DueDateDateTime><udt:DateTimeString format=\"102\">{$dueDate}</udt:DateTimeString></ram:DueDateDateTime>\n";
        $xml .= "      </ram:SpecifiedTradePaymentTerms>\n";
        $xml .= "      <ram:SpecifiedTradeSettlementHeaderMonetarySummation>\n";
        $xml .= "        <ram:TaxBasisTotalAmount>" . number_format((float) $invoice->subtotal, 2, '.', '') . "</ram:TaxBasisTotalAmount>\n";
        $xml .= "        <ram:TaxTotalAmount currencyID=\"" . htmlspecialchars($invoice->currency ?? 'EUR') . "\">{$taxAmount}</ram:TaxTotalAmount>\n";
        $xml .= "        <ram:GrandTotalAmount>{$totalAmount}</ram:GrandTotalAmount>\n";
        $xml .= "        <ram:DuePayableAmount>{$totalAmount}</ram:DuePayableAmount>\n";
        $xml .= "      </ram:SpecifiedTradeSettlementHeaderMonetarySummation>\n";
        $xml .= "    </ram:ApplicableHeaderTradeSettlement>\n";
        $xml .= "  </rsm:SupplyChainTradeTransaction>\n";
        $xml .= "</rsm:CrossIndustryInvoice>\n";

        return $xml;
    }
}
