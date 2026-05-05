<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $invoice->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 13px; color: #111; padding: 40px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .company { font-size: 22px; font-weight: bold; color: #4f46e5; }
        .meta { text-align: right; color: #555; }
        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #999; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 24px 0; }
        th { background: #f3f4f6; text-align: left; padding: 8px 12px; font-size: 10px; text-transform: uppercase; color: #6b7280; }
        td { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; }
        .totals { margin-left: auto; width: 260px; }
        .totals div { display: flex; justify-content: space-between; padding: 4px 0; }
        .totals .grand { font-weight: bold; font-size: 15px; border-top: 2px solid #111; padding-top: 8px; margin-top: 4px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: bold; }
        .paid { background: #d1fae5; color: #065f46; }
        .pending { background: #fef3c7; color: #92400e; }
        @media print { body { padding: 20px; } }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="company">{{ config('app.name') }}</div>
        </div>
        <div class="meta">
            <div style="font-size:16px;font-weight:bold;">{{ $invoice->number }}</div>
            <div>Émise le {{ $invoice->created_at->format('d/m/Y') }}</div>
            @if($invoice->paid_at)<div>Payée le {{ $invoice->paid_at->format('d/m/Y') }}</div>@endif
        </div>
    </div>

    <div style="margin-bottom:32px;">
        <div class="section-title">Facturé à</div>
        <div style="font-weight:bold;">{{ $invoice->user->full_name }}</div>
        <div>{{ $invoice->user->email }}</div>
        @if($invoice->user->company)<div>{{ $invoice->user->company }}</div>@endif
    </div>

    <table>
        <thead><tr><th>Description</th><th style="text-align:right;">Montant</th></tr></thead>
        <tbody>
            @foreach($invoice->items ?? [] as $item)
            <tr>
                <td>{{ $item['description'] ?? '—' }}</td>
                <td style="text-align:right;">{{ number_format($item['amount'] ?? 0, 2) }}€</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div><span>Sous-total HT</span><span>{{ number_format($invoice->subtotal, 2) }}€</span></div>
        <div><span>TVA</span><span>{{ number_format($invoice->tax, 2) }}€</span></div>
        <div class="grand"><span>Total TTC</span><span>{{ number_format($invoice->total, 2) }}€</span></div>
    </div>

    <div style="margin-top:40px;">
        <span class="badge {{ $invoice->isPaid() ? 'paid' : 'pending' }}">
            {{ $invoice->isPaid() ? 'Payée' : 'En attente' }}
        </span>
    </div>

    <script>window.onload = () => window.print();</script>
</body>
</html>
