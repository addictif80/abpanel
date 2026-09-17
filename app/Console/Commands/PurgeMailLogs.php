<?php

namespace App\Console\Commands;

use App\Models\MailLog;
use App\Models\Setting;
use Illuminate\Console\Command;

/**
 * GDPR storage-limitation guard (Art. 5§1.e): mail_logs stores the full
 * HTML body of every email ever sent (recipient, name, links...) with no
 * expiry — kept forever otherwise. Deletes rows older than a configurable
 * retention window (default 12 months, admin-tunable via Settings).
 */
class PurgeMailLogs extends Command
{
    protected $signature   = 'mail-logs:purge {--dry-run : Afficher sans supprimer}';
    protected $description = 'Supprime les journaux mails plus anciens que la durée de rétention configurée (RGPD)';

    public function handle(): int
    {
        $months = (int) Setting::get('mail_logs_retention_months', 12);
        $cutoff = now()->subMonths($months);

        $query = MailLog::where('created_at', '<', $cutoff);
        $count = $query->count();

        if ($this->option('dry-run')) {
            $this->info("{$count} journal(aux) mail antérieur(s) au {$cutoff->format('d/m/Y')} seraient supprimés (rétention : {$months} mois).");
            return self::SUCCESS;
        }

        $query->delete();
        $this->info("{$count} journal(aux) mail antérieur(s) au {$cutoff->format('d/m/Y')} supprimé(s) (rétention : {$months} mois).");

        return self::SUCCESS;
    }
}
