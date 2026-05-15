<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\CyberPanelService;
use App\Services\NginxProxyManagerService;
use App\Services\ProxmoxService;
use App\Services\StripeService;
use App\Services\TailscaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
    }

    public function saveGeneral(Request $request)
    {
        $request->validate([
            'app_name'          => 'required|string|max:50',
            'app_url'           => 'required|url',
            'support_email'     => 'required|email',
            'vms_base_domain'   => 'nullable|string',
            'registration_open' => 'boolean',
            'default_currency'  => 'required|string|size:3',
            'tax_rate'          => 'required|numeric|min:0|max:100',
        ]);

        $fields = ['app_name', 'app_url', 'support_email', 'vms_base_domain', 'default_currency', 'tax_rate'];
        foreach ($fields as $key) {
            Setting::set($key, $request->input($key), 'general');
        }
        Setting::set('registration_open', $request->boolean('registration_open') ? '1' : '0', 'general');

        return back()->with('success', 'Paramètres généraux enregistrés.');
    }

    public function saveProxmox(Request $request)
    {
        $request->validate([
            'proxmox_host'            => 'required|url',
            'proxmox_user'            => 'required|string',
            'proxmox_password'        => 'required|string',
            'proxmox_realm'           => 'required|string',
            'proxmox_node'            => 'required|string',
            'proxmox_default_storage' => 'nullable|string',
        ]);

        foreach (['proxmox_host', 'proxmox_user', 'proxmox_password', 'proxmox_realm', 'proxmox_node', 'proxmox_default_storage'] as $key) {
            Setting::set($key, $request->input($key, ''), 'proxmox');
        }

        return back()->with('success', 'Configuration Proxmox enregistrée.');
    }

    public function saveCyberpanel(Request $request)
    {
        $request->validate([
            'cyberpanel_host'     => 'required|url',
            'cyberpanel_user'     => 'required|string',
            'cyberpanel_password' => 'required|string',
        ]);

        foreach (['cyberpanel_host', 'cyberpanel_user', 'cyberpanel_password', 'cyberpanel_tailscale_ip'] as $key) {
            Setting::set($key, $request->input($key, ''), 'cyberpanel');
        }

        return back()->with('success', 'Configuration CyberPanel enregistrée.');
    }

    public function saveNpm(Request $request)
    {
        $request->validate([
            'npm_host'     => 'required|url',
            'npm_email'    => 'required|email',
            'npm_password' => 'required|string',
        ]);

        foreach (['npm_host', 'npm_email', 'npm_password'] as $key) {
            Setting::set($key, $request->input($key), 'npm');
        }
        Setting::set('domain_deletion_cooldown', (string) max(0, (int) $request->input('domain_deletion_cooldown', 60)), 'npm');

        return back()->with('success', 'Configuration NPM enregistrée.');
    }

    public function saveStripe(Request $request)
    {
        $request->validate([
            'stripe_public_key'    => 'required|string',
            'stripe_secret_key'    => 'required|string',
            'stripe_webhook_secret' => 'nullable|string',
        ]);

        foreach (['stripe_public_key', 'stripe_secret_key', 'stripe_webhook_secret'] as $key) {
            Setting::set($key, $request->input($key), 'stripe');
        }

        return back()->with('success', 'Configuration Stripe enregistrée.');
    }

    public function saveMail(Request $request)
    {
        $request->validate([
            'mail_host'         => 'required|string',
            'mail_port'         => 'required|integer',
            'mail_username'     => 'nullable|string',
            'mail_password'     => 'nullable|string',
            'mail_encryption'   => 'nullable|in:tls,ssl,',
            'mail_from_address' => 'required|email',
            'mail_from_name'    => 'required|string',
        ]);

        $fields = ['mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption', 'mail_from_address', 'mail_from_name'];
        foreach ($fields as $key) {
            Setting::set($key, $request->input($key), 'mail');
        }

        // Update runtime config
        config([
            'mail.mailers.smtp.host'       => $request->mail_host,
            'mail.mailers.smtp.port'       => $request->mail_port,
            'mail.mailers.smtp.username'   => $request->mail_username,
            'mail.mailers.smtp.password'   => $request->mail_password,
            'mail.mailers.smtp.encryption' => $request->mail_encryption,
            'mail.from.address'            => $request->mail_from_address,
            'mail.from.name'               => $request->mail_from_name,
        ]);

        return back()->with('success', 'Configuration mail enregistrée.');
    }

    public function saveTailscale(Request $request)
    {
        $request->validate([
            'tailscale_api_key'   => 'nullable|string|max:200',
            'tailscale_tailnet'   => 'nullable|string|max:200',
            'tailscale_auth_key'  => 'nullable|string|max:200',
        ]);

        foreach (['tailscale_api_key', 'tailscale_tailnet', 'tailscale_auth_key'] as $key) {
            Setting::set($key, $request->input($key, ''), 'tailscale');
        }

        return back()->with('success', 'Configuration Tailscale enregistrée.');
    }

    public function testConnection(Request $request, string $service)
    {
        try {
            $result = match($service) {
                'proxmox'    => app(ProxmoxService::class)->testConnection(),
                'cyberpanel' => app(CyberPanelService::class)->testConnection(),
                'npm'        => app(NginxProxyManagerService::class)->testConnection(),
                'stripe'     => app(StripeService::class)->testConnection(),
                'mail'       => $this->testMail($request->input('email', auth()->user()->email)),
                'tailscale'  => count(app(TailscaleService::class)->getDevices()) >= 0,
                default      => false,
            };

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Connexion réussie ✓' : 'Connexion échouée',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ]);
        }
    }

    public function saveCompany(Request $request)
    {
        $request->validate([
            'company_name'       => 'nullable|string|max:100',
            'company_siren'      => 'nullable|string|max:20',
            'company_vat_number' => 'nullable|string|max:20',
            'company_legal_form' => 'nullable|string|max:100',
            'company_rcs'        => 'nullable|string|max:100',
            'company_iban'       => 'nullable|string|max:34',
            'company_bic'        => 'nullable|string|max:11',
            'company_phone'      => 'nullable|string|max:30',
            'company_address'    => 'nullable|string|max:255',
        ]);

        foreach (['company_name', 'company_siren', 'company_vat_number', 'company_legal_form', 'company_rcs', 'company_iban', 'company_bic', 'company_phone', 'company_address'] as $key) {
            Setting::set($key, $request->input($key, ''), 'company');
        }

        return back()->with('success', 'Informations société enregistrées.');
    }

    public function saveEinvoicing(Request $request)
    {
        $request->validate([
            'ppf_siret' => 'nullable|string|max:20',
        ]);

        Setting::set('einvoicing_enabled', $request->boolean('einvoicing_enabled') ? '1' : '0', 'company');
        Setting::set('ppf_siret', $request->input('ppf_siret', ''), 'company');

        return back()->with('success', 'Paramètres facturation électronique enregistrés.');
    }

    public function saveQuotes(Request $request)
    {
        $request->validate([
            'quote_validity_days'  => 'required|integer|min:1|max:365',
            'invoice_payment_days' => 'required|integer|min:1|max:365',
            'invoice_late_penalty' => 'required|numeric|min:0|max:100',
            'invoice_recovery_fee' => 'required|numeric|min:0',
            'vat_mention'          => 'nullable|string|max:200',
            'quote_default_notes'  => 'nullable|string',
        ]);

        foreach (['quote_validity_days', 'invoice_payment_days', 'invoice_late_penalty', 'invoice_recovery_fee', 'vat_mention', 'quote_default_notes'] as $key) {
            Setting::set($key, $request->input($key, ''), 'quotes');
        }

        if ($request->hasFile('cgv_file')) {
            $request->validate(['cgv_file' => 'file|mimes:pdf|max:5120']);
            $path = $request->file('cgv_file')->store('cgv', 'public');
            Setting::set('cgv_path', $path, 'quotes');
        }

        return back()->with('success', 'Paramètres devis & facturation enregistrés.');
    }

    private function testMail(string $to): bool
    {
        Mail::raw('Test de connexion SMTP depuis ABPanel.', function ($msg) use ($to) {
            $msg->to($to)->subject('Test SMTP — ABPanel');
        });
        return true;
    }
}
