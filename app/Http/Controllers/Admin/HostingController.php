<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostingAccount;
use App\Models\User;
use App\Services\CyberPanelService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HostingController extends Controller
{
    public function index()
    {
        $accounts = HostingAccount::with('user')->latest()->paginate(25);
        return view('admin.hosting.index', compact('accounts'));
    }

    public function importIndex()
    {
        $sites = [];
        $error = null;

        try {
            $sites = app(CyberPanelService::class)->listAllWebsites();
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        $alreadyImported = HostingAccount::pluck('domain')->flip();

        $sites = array_map(function ($site) use ($alreadyImported) {
            $site['imported'] = $alreadyImported->has($site['domain'] ?? '');
            return $site;
        }, $sites);

        usort($sites, fn($a, $b) => $a['imported'] <=> $b['imported'] ?: strcmp($a['domain'] ?? '', $b['domain'] ?? ''));

        return view('admin.hosting.import-index', compact('sites', 'error'));
    }

    public function importShow(string $domain)
    {
        if (HostingAccount::where('domain', $domain)->exists()) {
            return redirect()->route('admin.hosting.import.index')
                ->with('error', "Le domaine « {$domain} » est déjà importé dans le panel.");
        }

        $siteInfo = null;
        try {
            $data = app(CyberPanelService::class)->getWebsiteData($domain);
            $siteInfo = [
                'domain'    => $domain,
                'owner'     => $data['websiteOwner'] ?? $data['owner'] ?? '',
                'package'   => $data['package'] ?? '',
                'disk_mb'   => isset($data['diskUsage']) ? (int) round($data['diskUsage'] * 1024) : 0,
                'email'     => $data['adminEmail'] ?? '',
            ];
        } catch (\Exception $e) {
            return redirect()->route('admin.hosting.import.index')
                ->with('error', 'Impossible de récupérer les infos CyberPanel : ' . $e->getMessage());
        }

        $clients = User::where('is_admin', false)->where('is_active', true)->orderBy('last_name')->get();

        return view('admin.hosting.import-show', compact('siteInfo', 'clients'));
    }

    public function importStore(Request $request, string $domain)
    {
        if (HostingAccount::where('domain', $domain)->exists()) {
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
        ]);

        $client   = User::findOrFail($request->user_id);
        $cyberpanel = app(CyberPanelService::class);
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
                    'cyberpanel_username' => $newUsername,
                    'cyberpanel_password' => $newPassword,
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

        HostingAccount::create([
            'user_id'             => $client->id,
            'domain'              => $domain,
            'cyberpanel_username' => $finalUsername ?: null,
            'plan'                => $request->plan,
            'disk_mb'             => $request->disk_mb ?? 0,
            'is_active'           => true,
            'monthly_price'       => $request->monthly_price,
            'next_renewal_at'     => $request->next_renewal_at,
        ]);

        $msg = "« {$domain} » importé et assigné à {$client->full_name}.";
        if ($warnings) {
            return redirect()->route('admin.hosting.index')
                ->with('success', $msg)
                ->with('warning', implode(' / ', $warnings));
        }

        return redirect()->route('admin.hosting.index')->with('success', $msg);
    }
}
