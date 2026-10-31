<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostingAccount;
use App\Models\InfraServer;
use App\Models\Plan;
use App\Models\User;
use App\Services\BillingImportService;
use App\Services\CyberPanelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HostingController extends Controller
{
    public function index()
    {
        $accounts = HostingAccount::with('user')->latest()->paginate(25);
        return view('admin.hosting.index', compact('accounts'));
    }

    public function importIndex(Request $request)
    {
        $servers  = InfraServer::ofType('cyberpanel')->orderByDesc('is_default')->orderBy('label')->get();
        $serverId = $request->query('server_id') ?: $servers->first()?->id;
        $server   = $servers->firstWhere('id', (int) $serverId);

        $sites = [];
        $error = null;

        if ($server) {
            try {
                $sites = (new CyberPanelService($server))->listAllWebsites();
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        $alreadyImported = HostingAccount::where('cyberpanel_server_id', $server?->id)->pluck('domain')->flip();

        $sites = array_map(function ($site) use ($alreadyImported) {
            $site['imported'] = $alreadyImported->has($site['domain'] ?? '');
            return $site;
        }, $sites);

        usort($sites, fn($a, $b) => $a['imported'] <=> $b['imported'] ?: strcmp($a['domain'] ?? '', $b['domain'] ?? ''));

        return view('admin.hosting.import-index', compact('sites', 'error', 'servers', 'server'));
    }

    public function importShow(InfraServer $server, string $domain)
    {
        if (HostingAccount::where('domain', $domain)->where('cyberpanel_server_id', $server->id)->exists()) {
            return redirect()->route('admin.hosting.import.index')
                ->with('error', "Le domaine « {$domain} » est déjà importé dans le panel.");
        }

        $siteInfo = null;
        try {
            $data = (new CyberPanelService($server))->findWebsite($domain);
            if (!$data) {
                return redirect()->route('admin.hosting.import.index')
                    ->with('error', "« {$domain} » n'a pas été retrouvé sur ce serveur CyberPanel.");
            }
            $siteInfo = [
                'domain'    => $domain,
                'owner'     => $data['admin'] ?? '',
                'package'   => $data['package'] ?? '',
                'disk_mb'   => $this->parseDiskUsed($data['diskUsed'] ?? ''),
                'email'     => $data['adminEmail'] ?? '',
            ];
        } catch (\Exception $e) {
            return redirect()->route('admin.hosting.import.index')
                ->with('error', 'Impossible de récupérer les infos CyberPanel : ' . $e->getMessage());
        }

        $clients = User::where('is_admin', false)->where('is_active', true)->orderBy('last_name')->get();
        $plans   = Plan::where('type', 'hosting')->orderBy('name')->get();

        return view('admin.hosting.import-show', compact('siteInfo', 'clients', 'plans', 'server'));
    }

    public function importStore(Request $request, InfraServer $server, string $domain)
    {
        if (HostingAccount::where('domain', $domain)->where('cyberpanel_server_id', $server->id)->exists()) {
            return redirect()->route('admin.hosting.import.index')
                ->with('error', "Le domaine « {$domain} » est déjà importé.");
        }

        $request->validate([
            'user_id'              => 'required|exists:users,id',
            'cyberpanel_username'  => 'nullable|string|max:100',
            'plan'                 => 'nullable|string|max:100',
            'disk_mb'              => 'nullable|integer|min:0',
            'monthly_price'        => 'required|numeric|min:0',
            'next_renewal_at'      => 'nullable|date',
            'create_cyberpanel_user' => 'nullable|boolean',
            'new_cyberpanel_username'=> 'nullable|string|max:100|alpha_num',
            'new_cyberpanel_password'=> 'nullable|string|min:8',
            'transfer_ownership'   => 'nullable|boolean',
            'plan_id'              => 'nullable|exists:plans,id',
            'billing_period'       => 'nullable|in:monthly,yearly',
            'promo_code'           => 'nullable|string',
            'paid_at'              => 'nullable|date',
        ]);

        $client   = User::findOrFail($request->user_id);
        $plan     = $request->plan_id ? Plan::findOrFail($request->plan_id) : null;
        $cyberpanel = new CyberPanelService($server);
        $warnings = [];

        $finalUsername = $request->cyberpanel_username ?? '';

        // Create a new CyberPanel user account if requested
        if ($request->boolean('create_cyberpanel_user') && $request->new_cyberpanel_username) {
            $newUsername = $request->new_cyberpanel_username;
            $newPassword = $request->new_cyberpanel_password
                ?: \Illuminate\Support\Str::random(10) . '!1';

            try {
                $cyberpanel->createUser(
                    $newUsername,
                    $newPassword,
                    $client->email,
                    trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''))
                );

                // Persist credentials on the user record
                $client->update([
                    'cyberpanel_username'  => $newUsername,
                    'cyberpanel_password'  => $newPassword,
                    'cyberpanel_server_id' => $server->id,
                ]);

                $finalUsername = $newUsername;
            } catch (\Throwable $e) {
                $warnings[] = 'Création utilisateur CyberPanel échouée : ' . $e->getMessage();
            }
        }

        // Transfer website ownership to the (new or existing) CyberPanel user
        if ($request->boolean('transfer_ownership') && $finalUsername) {
            try {
                $cyberpanel->changeWebsiteOwner($domain, $finalUsername);
            } catch (\Throwable $e) {
                $warnings[] = 'Transfert de propriété échoué : ' . $e->getMessage();
            }
        }

        // Invoice + resource creation must succeed or fail together: if the
        // HostingAccount row fails to insert, the client must not end up
        // billed for a hosting account that was never actually registered.
        try {
            $invoice = DB::transaction(function () use ($client, $plan, $request, $domain, $finalUsername, $server) {
                $invoice = $plan ? app(BillingImportService::class)->createPaidInvoice(
                    $client, $plan, $request->billing_period, $request->promo_code, $request->paid_at
                ) : null;

                HostingAccount::create([
                    'user_id'              => $client->id,
                    'plan_id'              => $plan?->id,
                    'domain'               => $domain,
                    'cyberpanel_username'  => $finalUsername ?: null,
                    'cyberpanel_server_id' => $server->id,
                    'plan'                 => $request->plan,
                    'disk_mb'              => $request->disk_mb ?? 0,
                    'is_active'            => true,
                    'monthly_price'        => $plan ? $plan->priceFor($invoice->recurrence_period) : $request->monthly_price,
                    'next_renewal_at'      => $invoice?->next_billing_at ?? $request->next_renewal_at,
                ]);

                return $invoice;
            });
        } catch (\App\Exceptions\InvalidPromoCodeException $e) {
            return back()->withErrors(['promo_code' => $e->getMessage()])->withInput();
        }

        $msg = "« {$domain} » importé et assigné à {$client->full_name}.";
        if ($invoice) {
            $msg .= " Facture {$invoice->number} créée et facturation récurrente activée.";
        }
        if ($warnings) {
            return redirect()->route('admin.hosting.index')
                ->with('success', $msg)
                ->with('warning', implode(' / ', $warnings));
        }

        return redirect()->route('admin.hosting.index')->with('success', $msg);
    }

    /** Parses CyberPanel's "diskUsed" string (e.g. "1MB", "2.5GB", "512KB") into whole MB. */
    private function parseDiskUsed(string $diskUsed): int
    {
        if (!preg_match('/([\d.]+)\s*(KB|MB|GB)?/i', trim($diskUsed), $m)) {
            return 0;
        }

        $value = (float) $m[1];
        $unit  = strtoupper($m[2] ?? 'MB');

        return (int) round(match ($unit) {
            'KB' => $value / 1024,
            'GB' => $value * 1024,
            default => $value,
        });
    }
}
