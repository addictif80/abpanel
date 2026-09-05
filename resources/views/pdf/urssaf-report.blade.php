<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Déclaration URSSAF - {{ ucfirst($month->translatedFormat('F Y')) }}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; padding: 40px; }
.header { display: flex; justify-content: space-between; margin-bottom: 28px; }
.brand { font-size: 22px; font-weight: bold; color: #4f46e5; }
.company-meta { font-size: 10px; color: #6b7280; margin-top: 3px; line-height: 1.6; }
.doc-title { font-size: 20px; font-weight: bold; color: #111; }
.doc-ref { font-size: 11px; color: #555; margin-top: 4px; }
.label { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #9ca3af; margin-bottom: 3px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
th { background: #f3f4f6; text-align: left; padding: 8px 10px; font-size: 9px; text-transform: uppercase; color: #6b7280; border-bottom: 2px solid #e5e7eb; }
td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; font-size: 11px; vertical-align: top; }
td.right { text-align: right; }
td.bold { font-weight: bold; }
.summary-box { margin-top: 10px; padding: 16px 20px; background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 6px; }
.summary-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; color: #4b5563; }
.summary-total { display: flex; justify-content: space-between; padding: 10px 0 4px; font-weight: bold; font-size: 16px; color: #4338ca; border-top: 2px solid #c7d2fe; margin-top: 6px; }
.reminder-box { margin-top: 24px; padding: 14px 18px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; font-size: 10.5px; color: #92400e; line-height: 1.7; }
.footer { margin-top: 24px; padding-top: 14px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #9ca3af; text-align: center; }
.empty { color: #9ca3af; font-style: italic; padding: 16px 0; }
</style>
</head>
<body>

<div class="header">
    <div>
        <div class="brand">{{ $settings['company_name'] ?? $settings['app_name'] ?? config('app.name') }}</div>
        <div class="company-meta">
            @if(!empty($settings['company_legal_form'])){{ $settings['company_legal_form'] }}@endif
            @if(!empty($settings['company_siren'])) — SIREN {{ $settings['company_siren'] }}@endif
            @if(!empty($settings['company_address']))<br>{{ $settings['company_address'] }}@endif
        </div>
    </div>
    <div style="text-align:right;">
        <div class="doc-title">DÉCLARATION URSSAF</div>
        <div class="doc-ref">{{ ucfirst($month->translatedFormat('F Y')) }}</div>
        <div class="doc-ref">Période du {{ $start->format('d/m/Y') }} au {{ $end->format('d/m/Y') }}</div>
    </div>
</div>

<div class="label">Détail des transactions encaissées (factures payées)</div>
<table>
    <thead>
        <tr>
            <th>N° Facture</th>
            <th>Client</th>
            <th>Date d'encaissement</th>
            <th style="text-align:right;width:100px;">Montant TTC</th>
        </tr>
    </thead>
    <tbody>
        @forelse($invoices as $invoice)
        <tr>
            <td>{{ $invoice->number }}</td>
            <td>{{ $invoice->user->full_name ?? $invoice->user->name ?? '—' }}</td>
            <td>{{ $invoice->paid_at?->format('d/m/Y') ?? '—' }}</td>
            <td class="right bold">{{ number_format($invoice->total, 2) }} {{ $invoice->currency ?? 'EUR' }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="empty">Aucune transaction encaissée sur cette période.</td></tr>
        @endforelse
    </tbody>
</table>

@if($creditNotes->count() > 0)
<div class="label">Avoirs émis sur la période (à déduire)</div>
<table>
    <thead>
        <tr>
            <th>N° Avoir</th>
            <th>Client</th>
            <th>Date</th>
            <th style="text-align:right;width:100px;">Montant</th>
        </tr>
    </thead>
    <tbody>
        @foreach($creditNotes as $note)
        <tr>
            <td>{{ $note->number }}</td>
            <td>{{ $note->user->full_name ?? $note->user->name ?? '—' }}</td>
            <td>{{ $note->issued_at?->format('d/m/Y') ?? '—' }}</td>
            <td class="right bold">- {{ number_format($note->amount, 2) }} {{ $note->currency ?? 'EUR' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="summary-box">
    <div class="summary-row"><span>Total encaissé (factures payées)</span><span>{{ number_format($totalEncaisse, 2) }} €</span></div>
    @if($totalAvoirs > 0)
    <div class="summary-row"><span>Avoirs déduits</span><span>- {{ number_format($totalAvoirs, 2) }} €</span></div>
    @endif
    <div class="summary-total"><span>Chiffre d'affaires à déclarer</span><span>{{ number_format($totalNet, 2) }} €</span></div>
</div>

<div class="reminder-box">
    <strong>Rappel :</strong> ce montant correspond au chiffre d'affaires encaissé sur la période et doit être reporté dans votre déclaration mensuelle URSSAF (autoentrepreneur.urssaf.fr), en fonction de votre catégorie d'activité (prestations de services BIC/BNC). Vérifiez la date limite de déclaration de votre échéance auprès de l'URSSAF.
    {{ $settings['vat_mention'] ?? 'TVA non applicable, art. 293 B du CGI' }}.
</div>

<div class="footer">
    {{ $settings['company_name'] ?? $settings['app_name'] ?? config('app.name') }}
    @if(!empty($settings['company_siren'])) — SIREN {{ $settings['company_siren'] }} @endif
    — Document généré automatiquement le {{ now()->format('d/m/Y') }}
</div>

</body>
</html>
