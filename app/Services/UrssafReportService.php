<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class UrssafReportService
{
    public function generateForMonth(Carbon $month): array
    {
        $month = $month->copy()->locale('fr');
        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();

        $invoices = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->with('user')
            ->orderBy('paid_at')
            ->get();

        $creditNotes = CreditNote::whereIn('status', ['issued', 'applied'])
            ->whereBetween('issued_at', [$start, $end])
            ->with('user')
            ->orderBy('issued_at')
            ->get();

        $totalEncaisse = (float) $invoices->sum('total');
        $totalAvoirs   = (float) $creditNotes->sum('amount');
        $totalNet      = $totalEncaisse - $totalAvoirs;

        $settings = Setting::group('general') + Setting::group('company') + Setting::group('quotes');

        $pdf = Pdf::loadView('pdf.urssaf-report', [
            'month'          => $month,
            'start'          => $start,
            'end'            => $end,
            'invoices'       => $invoices,
            'creditNotes'    => $creditNotes,
            'totalEncaisse'  => $totalEncaisse,
            'totalAvoirs'    => $totalAvoirs,
            'totalNet'       => $totalNet,
            'settings'       => $settings,
        ])->setPaper('a4');

        return [
            'pdf'             => $pdf->output(),
            'invoices_count'  => $invoices->count(),
            'total_encaisse'  => $totalEncaisse,
            'total_avoirs'    => $totalAvoirs,
            'total_net'       => $totalNet,
            'period_label'    => $month->translatedFormat('F Y'),
            'filename'        => 'declaration-urssaf-' . $month->format('Y-m') . '.pdf',
        ];
    }
}
