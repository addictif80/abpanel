<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientDomain;
use App\Models\HostingAccount;
use App\Models\Plan;
use App\Models\User;
use App\Models\VirtualMachine;
use App\Services\BillingImportService;
use App\Services\NginxProxyManagerService;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function index()
    {
        $domains = ClientDomain::with(['user', 'virtualMachine', 'hostingAccount'])
            ->latest()
            ->paginate(50);

        return view('admin.domains.index', compact('domains'));
    }

    public function destroy(ClientDomain $domain)
    {
        if ($domain->npm_proxy_id) {
            try {
                app(NginxProxyManagerService::class)->deleteProxyHost($domain->npm_proxy_id);
            } catch (\Throwable) {}
        }

        $domain->delete();

        return back()->with('success', "Domaine « {$domain->domain} » supprimé.");
    }

    // ── Import existing NPM proxy hosts ───────────────────────────────────

    public function importIndex()
    {
        $hosts = [];
        $error = null;

        try {
            $hosts = app(NginxProxyManagerService::class)->listProxyHosts();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $importedProxyIds = ClientDomain::withTrashed()->whereNotNull('npm_proxy_id')->pluck('npm_proxy_id')->flip();

        $hosts = array_map(function ($host) use ($importedProxyIds) {
            $host['imported'] = $importedProxyIds->has($host['id']);
            return $host;
        }, $hosts);

        usort($hosts, fn($a, $b) => $a['imported'] <=> $b['imported'] ?: strcmp($a['domain_names'][0] ?? '', $b['domain_names'][0] ?? ''));

        return view('admin.domains.import-index', compact('hosts', 'error'));
    }

    public function importShow(int $hostId)
    {
        if (ClientDomain::withTrashed()->where('npm_proxy_id', $hostId)->exists()) {
            return redirect()->route('admin.domains.import.index')
                ->with('error', "Cet hôte NPM (#{$hostId}) est déjà importé dans le panel.");
        }

        try {
            $host = app(NginxProxyManagerService::class)->getProxyHost($hostId);
        } catch (\Throwable $e) {
            return redirect()->route('admin.domains.import.index')
                ->with('error', 'Impossible de récupérer les infos NPM : ' . $e->getMessage());
        }

        $clients = User::where('is_admin', false)->where('is_active', true)->orderBy('last_name')->get();
        $plans   = Plan::where('type', 'service')->orderBy('name')->get();

        return view('admin.domains.import-show', compact('host', 'clients', 'plans'));
    }

    public function importStore(Request $request, int $hostId)
    {
        if (ClientDomain::withTrashed()->where('npm_proxy_id', $hostId)->exists()) {
            return redirect()->route('admin.domains.import.index')
                ->with('error', "Cet hôte NPM (#{$hostId}) est déjà importé.");
        }

        $request->validate([
            'user_id'             => 'required|exists:users,id',
            'domain'              => 'required|string|max:253',
            'type'                => 'required|in:vps,hosting',
            'virtual_machine_id'  => 'nullable|exists:virtual_machines,id',
            'hosting_account_id'  => 'nullable|exists:hosting_accounts,id',
            'plan_id'             => 'nullable|exists:plans,id',
            'billing_period'      => 'nullable|in:monthly,yearly',
            'promo_code'          => 'nullable|string',
            'paid_at'             => 'nullable|date',
        ]);

        $client  = User::findOrFail($request->user_id);
        $plan    = $request->plan_id ? Plan::findOrFail($request->plan_id) : null;
        $invoice = null;

        if ($plan) {
            try {
                $invoice = app(BillingImportService::class)->createPaidInvoice(
                    $client, $plan, $request->billing_period, $request->promo_code, $request->paid_at
                );
            } catch (\RuntimeException $e) {
                return back()->withErrors(['promo_code' => $e->getMessage()])->withInput();
            }
        }

        try {
            $host = app(NginxProxyManagerService::class)->getProxyHost($hostId);
        } catch (\Throwable $e) {
            return back()->withErrors(['npm' => 'Impossible de relire l\'hôte NPM : ' . $e->getMessage()])->withInput();
        }

        $sslCertId = $host['certificate_id'] ?? null;
        $sslExpiresAt = null;
        if ($sslCertId) {
            try {
                $sslExpiresAt = app(NginxProxyManagerService::class)->getCertificateExpiry($sslCertId);
            } catch (\Throwable) {}
        }

        ClientDomain::create([
            'user_id'             => $client->id,
            'virtual_machine_id'  => $request->type === 'vps' ? $request->virtual_machine_id : null,
            'hosting_account_id'  => $request->type === 'hosting' ? $request->hosting_account_id : null,
            'domain'              => $request->domain,
            'type'                => $request->type,
            'target_ip'           => $host['forward_host'] ?? '',
            'target_port'         => $host['forward_port'] ?? 80,
            'forward_scheme'      => $host['forward_scheme'] ?? 'http',
            'www_redirect'        => count($host['domain_names'] ?? []) > 1,
            'ssl_enabled'         => (bool) $sslCertId,
            'npm_proxy_id'        => $hostId,
            'ssl_certificate_id'  => $sslCertId ?: null,
            'ssl_expires_at'      => $sslExpiresAt,
            'dns_ok'              => true,
            'dns_checked_at'      => now(),
        ]);

        $msg = "« {$request->domain} » importé et assigné.";
        if ($invoice) {
            $msg .= " Facture {$invoice->number} créée et facturation récurrente activée.";
        }

        return redirect()->route('admin.domains.index')->with('success', $msg);
    }

    /** AJAX: VMs/hosting accounts owned by a client, for the import form's dependent select. */
    public function clientResources(User $user)
    {
        return response()->json([
            'virtual_machines' => $user->virtualMachines()->get(['id', 'name']),
            'hosting_accounts' => $user->hostingAccounts()->get(['id', 'domain']),
        ]);
    }
}
