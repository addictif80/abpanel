<?php
// À copier dans abpanel : app/Observers/PlanObserver.php
// Prévient le site abhd.fr dès qu'un tarif est créé / modifié / supprimé → mise à jour instantanée.
// Variables .env à ajouter dans abpanel :
//   ABHD_SITE_WEBHOOK_URL=https://abhd.fr/api/webhooks/pricing
//   ABHD_SITE_WEBHOOK_SECRET=<secret affiché dans Admin > Tarifs synchronisés>

namespace App\Observers;

use App\Models\Plan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlanObserver
{
    public bool $afterCommit = true;

    public function saved(Plan $plan): void   { $this->ping(); }
    public function deleted(Plan $plan): void { $this->ping(); }

    private function ping(): void
    {
        $url = config('services.abhd_site.webhook_url');
        if (!$url) {
            return;
        }
        try {
            Http::timeout(3)
                ->withHeaders(['X-Webhook-Secret' => (string) config('services.abhd_site.webhook_secret')])
                ->post($url, ['source' => 'abpanel']);
        } catch (\Throwable $e) {
            // Ne jamais bloquer l'enregistrement d'un tarif : le site rattrapera au prochain passage (polling).
            Log::warning('Webhook tarifs abhd.fr échoué : ' . $e->getMessage());
        }
    }
}
