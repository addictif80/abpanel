<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\MailService;
use App\Services\UrssafReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendUrssafReport extends Command
{
    protected $signature   = 'urssaf:report {--month= : Mois à traiter au format YYYY-MM (par défaut le mois précédent)} {--force : Ignorer le jour configuré et l\'activation} {--dry-run : Afficher sans envoyer}';
    protected $description = 'Génère le rapport mensuel des transactions et envoie un rappel de déclaration URSSAF';

    public function __construct(
        private readonly MailService $mail,
        private readonly UrssafReportService $reports,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dry   = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $enabled = Setting::get('urssaf_report_enabled', '0') === '1';
        if (!$enabled && !$force) {
            $this->info('Rapport URSSAF désactivé (Paramètres > Devis & Facturation).');
            return self::SUCCESS;
        }

        $reportDay = (int) Setting::get('urssaf_report_day', 1);
        if (!$force && now()->day !== $reportDay) {
            $this->info("Ce n'est pas le jour configuré pour l'envoi ({$reportDay}).");
            return self::SUCCESS;
        }

        $recipient = Setting::get('urssaf_recipient_email') ?: Setting::get('support_email');
        if (!$recipient) {
            $this->error('Aucun email destinataire configuré (urssaf_recipient_email ou support_email).');
            return self::FAILURE;
        }

        $month = $this->option('month')
            ? Carbon::createFromFormat('Y-m', $this->option('month'))->startOfMonth()
            : now()->subMonthNoOverflow()->startOfMonth();

        $report = $this->reports->generateForMonth($month);

        $this->info(sprintf(
            'Rapport %s : %d facture(s), %s € à déclarer → %s',
            $report['period_label'],
            $report['invoices_count'],
            number_format($report['total_net'], 2),
            $recipient,
        ));

        if ($dry) {
            return self::SUCCESS;
        }

        $this->mail->sendFromTemplate('urssaf_monthly_report', $recipient, [
            'period_label'   => $report['period_label'],
            'total_amount'   => number_format($report['total_net'], 2) . ' €',
            'invoices_count' => (string) $report['invoices_count'],
        ], [
            [
                'data' => $report['pdf'],
                'name' => $report['filename'],
                'mime' => 'application/pdf',
            ],
        ]);

        return self::SUCCESS;
    }
}
