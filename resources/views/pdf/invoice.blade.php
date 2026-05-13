<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Facture {{ $invoice->number }}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; padding: 40px; }
.header { display: flex; justify-content: space-between; margin-bottom: 28px; }
.brand { font-size: 22px; font-weight: bold; color: #4f46e5; }
.company-meta { font-size: 10px; color: #6b7280; margin-top: 3px; line-height: 1.6; }
.doc-title { font-size: 20px; font-weight: bold; color: #111; }
.doc-ref { font-size: 11px; color: #555; margin-top: 4px; }
.client-block { margin-bottom: 22px; }
.label { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #9ca3af; margin-bottom: 3px; }
.client-name { font-weight: bold; font-size: 13px; margin-bottom: 3px; }
.client-detail { color: #555; font-size: 11px; line-height: 1.6; }
table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
th { background: #f3f4f6; text-align: left; padding: 8px 10px; font-size: 9px; text-transform: uppercase; color: #6b7280; border-bottom: 2px solid #e5e7eb; }
td { padding: 9px 10px; border-bottom: 1px solid #f3f4f6; font-size: 11px; vertical-align: top; }
td.right { text-align: right; }
td.bold { font-weight: bold; }
.totals { width: 220px; margin-left: auto; }
.totals-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 11px; color: #555; }
.totals-total { display: flex; justify-content: space-between; padding: 8px 0 4px; font-weight: bold; font-size: 14px; color: #111; border-top: 2px solid #111; margin-top: 4px; }
.vat-note { font-size: 9px; color: #9ca3af; margin-top: 6px; }
.status-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 10px; font-weight: bold; }
.paid { background: #d1fae5; color: #065f46; }
.pending { background: #fef3c7; color: #92400e; }
.legal-box { margin-top: 28px; padding: 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 9px; color: #6b7280; line-height: 1.7; }
.footer { margin-top: 24px; padding-top: 14px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #9ca3af; text-align: center; }
</style>
</head>
<body>

<div class="header">
    <div>
        <div class="brand">{{ $settings['app_name'] ?? config('app.name') }}</div>
        <div class="company-meta">
            @if(!empty($settings['company_legal_form'])){{ $settings['company_legal_form'] }}@endif
            @if(!empty($settings['company_siren'])) — SIREN {{ $settings['company_siren'] }}@endif
            @if(!empty($settings['company_rcs']))<br>{{ $settings['company_rcs'] }}@endif
            @if(!empty($settings['company_address']))<br>{{ $settings['company_address'] }}@endif
            @if(!empty($settings['company_phone']))<br>Tél. {{ $settings['company_phone'] }}@endif
            @if(!empty($settings['support_email']))<br>{{ $settings['support_email'] }}@endif
            @if(!empty($settings['company_iban']))<br>IBAN : {{ $settings['company_iban'] }}@if(!empty($settings['company_bic'])) — BIC : {{ $settings['company_bic'] }}@endif@endif
        </div>
    </div>
    <div style="text-align:right;">
        <div class="doc-title">FACTURE</div>
        <div class="doc-ref">{{ $invoice->number }}</div>
        <div class="doc-ref">Émise le {{ $invoice->created_at->format('d/m/Y') }}</div>
        @if($invoice->due_at)
        <div class="doc-ref">Échéance : {{ $invoice->due_at->format('d/m/Y') }}</div>
        @endif
        @if($invoice->quote)
        <div class="doc-ref" style="color:#6b7280;">Devis : {{ $invoice->quote->number }}</div>
        @endif
        <div style="margin-top:8px;">
            <span class="status-badge {{ $invoice->isPaid() ? 'paid' : 'pending' }}">
                {{ $invoice->isPaid() ? 'Payée' : 'En attente' }}
            </span>
        </div>
    </div>
</div>

<div class="client-block">
    <div class="label">Facturé à</div>
    <div class="client-name">{{ $invoice->user->full_name }}</div>
    <div class="client-detail">
        {{ $invoice->user->email }}
        @if($invoice->user->company)<br>{{ $invoice->user->company }}@endif
        @if($invoice->user->address)<br>{{ $invoice->user->address }}@endif
        @if($invoice->user->zip || $invoice->user->city)<br>{{ $invoice->user->zip }} {{ $invoice->user->city }}@endif
        @if($invoice->user->siret)<br>SIRET : {{ $invoice->user->siret }}@endif
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Description</th>
            <th style="text-align:right;width:60px;">Qté</th>
            <th style="text-align:right;width:90px;">Prix unit.</th>
            <th style="text-align:right;width:100px;">Montant HT</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items ?? [] as $item)
        <tr>
            <td>{{ $item['description'] ?? '—' }}</td>
            <td class="right">{{ $item['quantity'] ?? 1 }}</td>
            <td class="right">{{ number_format($item['unit_price'] ?? 0, 2) }} €</td>
            <td class="right bold">{{ number_format($item['total'] ?? $item['amount'] ?? 0, 2) }} €</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="totals">
    <div class="totals-row"><span>Sous-total HT</span><span>{{ number_format($invoice->subtotal, 2) }} €</span></div>
    @if($invoice->tax > 0)
    <div class="totals-row"><span>TVA</span><span>{{ number_format($invoice->tax, 2) }} €</span></div>
    @endif
    <div class="totals-total"><span>Total TTC</span><span>{{ number_format($invoice->total, 2) }} {{ $invoice->currency ?? 'EUR' }}</span></div>
    <div class="vat-note">{{ $settings['vat_mention'] ?? 'TVA non applicable, art. 293 B du CGI' }}</div>
    @if($invoice->isPaid() && $invoice->paid_at)
    <div style="margin-top:8px;font-size:10px;color:#16a34a;font-weight:bold;">Payée le {{ $invoice->paid_at->format('d/m/Y') }}</div>
    @endif
</div>

<div class="legal-box">
    @php
        $latePenalty  = $settings['invoice_late_penalty'] ?? '10';
        $recoveryFee  = $settings['invoice_recovery_fee'] ?? '40';
        $vatMention   = $settings['vat_mention'] ?? 'TVA non applicable, art. 293 B du CGI';
        $paymentDays  = $settings['invoice_payment_days'] ?? '30';
    @endphp
    <strong>Conditions de paiement</strong> : Règlement à {{ $paymentDays }} jours à compter de la date de facturation.<br>
    En cas de retard de paiement, des pénalités au taux de {{ $latePenalty }}% par an seront appliquées, ainsi qu'une indemnité forfaitaire de recouvrement de {{ $recoveryFee }} €.<br>
    {{ $vatMention }}.
    @if(!empty($settings['company_siren']))
    SIREN : {{ $settings['company_siren'] }}.
    @endif
</div>

<div class="footer">
    {{ $settings['app_name'] ?? config('app.name') }}
    @if(!empty($settings['company_siren'])) — SIREN {{ $settings['company_siren'] }} @endif
    @if(!empty($settings['company_legal_form'])) — {{ $settings['company_legal_form'] }} @endif
</div>

</body>
</html>
