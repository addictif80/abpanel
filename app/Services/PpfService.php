<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * Portail Public de Facturation (Chorus Pro) integration stub.
 *
 * Chorus Pro API v2 base URL: https://api.chorus-pro.gouv.fr/api/
 * Authentication: OAuth2 PISTE (https://piste.gouv.fr)
 *
 * This service will be wired up once PISTE credentials are obtained.
 * The full flow is: authenticate → upload Factur-X PDF → track status.
 */
class PpfService
{
    private const PISTE_TOKEN_URL  = 'https://oauth.piste.gouv.fr/api/oauth/token';
    private const CHORUS_API_BASE  = 'https://api.chorus-pro.gouv.fr/api/';

    public function isConfigured(): bool
    {
        return filled(Setting::get('ppf_siret'))
            && filled(Setting::get('ppf_piste_client_id'))
            && filled(Setting::get('ppf_piste_client_secret'));
    }

    /**
     * Transmit an invoice PDF to Chorus Pro.
     * Returns the Chorus Pro flow ID on success, throws on failure.
     */
    public function transmit(Invoice $invoice, string $pdfContent): string
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('PPF/Chorus Pro not configured. Set ppf_siret, ppf_piste_client_id, ppf_piste_client_secret in settings.');
        }

        // TODO: implement PISTE OAuth2 token retrieval
        // $token = $this->getAccessToken();

        // TODO: implement Chorus Pro API v2 deposit
        // POST /api/cpro/factures/v1/deposer/flux
        // multipart/form-data: fichierFlux (PDF), syntaxeFlux (IN_DP_E1_CII_EN16931_FX_V1), nomFichier

        Log::info("PPF: Would transmit invoice {$invoice->number} to Chorus Pro");

        throw new \RuntimeException('PPF transmission not yet implemented. Credentials needed.');
    }

    /**
     * Check transmission status for a previously submitted flow.
     */
    public function getStatus(string $flowId): array
    {
        // TODO: GET /api/cpro/factures/v1/consulter/flux/{idFlux}
        return ['status' => 'unknown', 'flowId' => $flowId];
    }

    private function getAccessToken(): string
    {
        $clientId     = Setting::get('ppf_piste_client_id');
        $clientSecret = Setting::get('ppf_piste_client_secret');

        $response = \Illuminate\Support\Facades\Http::asForm()->post(self::PISTE_TOKEN_URL, [
            'grant_type'    => 'client_credentials',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'scope'         => 'openid',
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('PISTE token request failed: ' . $response->body());
        }

        return $response->json('access_token');
    }
}
