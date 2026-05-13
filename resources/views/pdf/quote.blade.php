<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Devis {{ $quote->number }}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; padding: 40px; }
.header { display: flex; justify-content: space-between; margin-bottom: 36px; }
.brand { font-size: 22px; font-weight: bold; color: #4f46e5; }
.label { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #9ca3af; margin-bottom: 3px; }
.doc-title { font-size: 20px; font-weight: bold; color: #111; }
.doc-ref { font-size: 11px; color: #555; margin-top: 4px; }
.parties { display: flex; justify-content: space-between; margin-bottom: 28px; }
.party { width: 45%; }
.party-name { font-weight: bold; font-size: 13px; margin-bottom: 3px; }
.party-detail { color: #555; font-size: 11px; line-height: 1.6; }
table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
th { background: #f3f4f6; text-align: left; padding: 8px 10px; font-size: 9px; text-transform: uppercase; color: #6b7280; border-bottom: 2px solid #e5e7eb; }
td { padding: 9px 10px; border-bottom: 1px solid #f3f4f6; font-size: 11px; vertical-align: top; }
td.right { text-align: right; }
td.bold { font-weight: bold; }
.totals { width: 220px; margin-left: auto; }
.totals-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 11px; color: #555; }
.totals-total { display: flex; justify-content: space-between; padding: 8px 0 4px; font-weight: bold; font-size: 14px; color: #111; border-top: 2px solid #111; margin-top: 4px; }
.vat-note { font-size: 9px; color: #9ca3af; margin-top: 6px; }
.notes { margin-top: 24px; padding: 14px; background: #f9fafb; border-left: 3px solid #e5e7eb; }
.notes-title { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #6b7280; margin-bottom: 6px; }
.notes p { font-size: 11px; color: #374151; line-height: 1.6; }
.footer { margin-top: 36px; padding-top: 14px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #9ca3af; text-align: center; }
.status-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 10px; font-weight: bold; background: #e0e7ff; color: #3730a3; }
.deposit-box { margin-top: 8px; padding: 8px 10px; background: #eff6ff; border-radius: 6px; font-size: 10px; color: #1d4ed8; }
</style>
</head>
<body>

<div class="header">
    <div>
        <div class="brand">{{ $settings['app_name'] ?? config('app.name') }}</div>
        @if(!empty($settings['company_siren']))
        <div style="font-size:10px;color:#6b7280;margin-top:3px;">
            {{ $settings['company_legal_form'] ?? '' }} — SIREN {{ $settings['company_siren'] }}
            @if(!empty($settings['company_rcs'])) — {{ $settings['company_rcs'] }} @endif
        </div>
        @endif
    </div>
    <div style="text-align:right;">
        <div class="doc-title">DEVIS</div>
        <div class="doc-ref">{{ $quote->number }}</div>
        <div class="doc-ref">Émis le {{ $quote->created_at->format('d/m/Y') }}</div>
        @if($quote->expires_at)
        <div class="doc-ref">Valable jusqu'au {{ $quote->expires_at->format('d/m/Y') }}</div>
        @endif
        <div style="margin-top:8px;"><span class="status-badge">{{ $quote->statusLabel() }}</span></div>
    </div>
</div>

<div class="parties">
    <div class="party">
        <div class="label">Émetteur</div>
        <div class="party-name">{{ $settings['app_name'] ?? config('app.name') }}</div>
        @if(!empty($settings['company_siren']))
        <div class="party-detail">{{ $settings['company_legal_form'] ?? 'Micro-entreprise' }}<br>SIREN : {{ $settings['company_siren'] }}</div>
        @endif
    </div>
    <div class="party">
        <div class="label">Client</div>
        <div class="party-name">{{ $quote->user->full_name }}</div>
        <div class="party-detail">
            {{ $quote->user->email }}
            @if($quote->user->company)<br>{{ $quote->user->company }}@endif
            @if($quote->user->address)<br>{{ $quote->user->address }}@endif
            @if($quote->user->zip || $quote->user->city)<br>{{ $quote->user->zip }} {{ $quote->user->city }}@endif
            @if($quote->user->siret)<br>SIRET : {{ $quote->user->siret }}@endif
        </div>
    </div>
</div>

@if($quote->subject)
<div style="font-size:13px;font-weight:bold;color:#374151;margin-bottom:18px;">Objet : {{ $quote->subject }}</div>
@endif

<table>
    <thead>
        <tr>
            <th>Prestation</th>
            <th style="text-align:right;width:60px;">Qté</th>
            <th style="text-align:right;width:50px;">Unité</th>
            <th style="text-align:right;width:80px;">Prix unit.</th>
            <th style="text-align:right;width:70px;">Remise</th>
            <th style="text-align:right;width:90px;">Total HT</th>
        </tr>
    </thead>
    <tbody>
        @foreach($quote->items as $item)
        <tr>
            <td>
                <strong>{{ $item->description }}</strong>
                @if($item->details)<br><span style="color:#6b7280;font-size:10px;">{{ $item->details }}</span>@endif
            </td>
            <td class="right">{{ number_format($item->quantity, 2) }}</td>
            <td class="right">{{ $item->unit }}</td>
            <td class="right">{{ number_format($item->unit_price, 2) }} €</td>
            <td class="right">
                @if($item->discount_amount > 0)
                    {{ $item->discount_type === 'percent' ? $item->discount_amount . '%' : number_format($item->discount_amount, 2) . ' €' }}
                @else —
                @endif
            </td>
            <td class="right bold">{{ number_format($item->total, 2) }} €</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="totals">
    <div class="totals-row"><span>Sous-total HT</span><span>{{ number_format($quote->subtotal, 2) }} €</span></div>
    @if($quote->discount_amount > 0)
    <div class="totals-row" style="color:#16a34a;">
        <span>Remise globale</span>
        <span>- {{ $quote->discount_type === 'percent' ? $quote->discount_amount . '%' : number_format($quote->discount_amount, 2) . ' €' }}</span>
    </div>
    @endif
    <div class="totals-total"><span>Total HT</span><span>{{ number_format($quote->total, 2) }} {{ $quote->currency }}</span></div>
    <div class="vat-note">{{ $settings['vat_mention'] ?? 'TVA non applicable, art. 293 B du CGI' }}</div>
    @if($quote->deposit_percent > 0)
    <div class="deposit-box">
        Acompte ({{ $quote->deposit_percent }}%) : {{ number_format($quote->depositAmount(), 2) }} €<br>
        Solde : {{ number_format($quote->balanceAmount(), 2) }} €
    </div>
    @endif
</div>

@if($quote->notes)
<div class="notes">
    <div class="notes-title">Remarques</div>
    <p>{{ $quote->notes }}</p>
</div>
@endif

<div class="footer">
    {{ $settings['app_name'] ?? config('app.name') }}
    @if(!empty($settings['company_siren'])) — SIREN {{ $settings['company_siren'] }} @endif
    @if(!empty($settings['company_legal_form'])) — {{ $settings['company_legal_form'] }} @endif
    @if(!empty($settings['company_iban'])) — IBAN {{ $settings['company_iban'] }} @if(!empty($settings['company_bic'])) BIC {{ $settings['company_bic'] }} @endif @endif
</div>

</body>
</html>
