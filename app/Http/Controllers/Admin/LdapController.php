<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Services\BillingImportService;
use App\Services\LdapService;
use App\Services\ProvisioningService;
use Illuminate\Http\Request;

class LdapController extends Controller
{
    public function index()
    {
        $users  = [];
        $groups = [];
        $error  = null;

        try {
            $users  = app(LdapService::class)->listUsers();
            $groups = app(LdapService::class)->listGroups();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return view('admin.ldap.index', compact('users', 'groups', 'error'));
    }

    // ── Import existing LDAP accounts (cloud, mail, ...) ──────────────────

    public function importIndex()
    {
        $ldapUsers = [];
        $error     = null;

        try {
            $ldapUsers = app(LdapService::class)->listUsers();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $alreadyImported = User::whereNotNull('ldap_username')->pluck('ldap_username')->flip();

        $ldapUsers = array_map(function ($u) use ($alreadyImported) {
            $u['imported'] = $alreadyImported->has($u['uid']);
            return $u;
        }, $ldapUsers);

        usort($ldapUsers, fn($a, $b) => $a['imported'] <=> $b['imported'] ?: strcmp($a['uid'], $b['uid']));

        return view('admin.ldap.import-index', compact('ldapUsers', 'error'));
    }

    public function importShow(string $uid)
    {
        if (User::where('ldap_username', $uid)->exists()) {
            return redirect()->route('admin.ldap.import.index')
                ->with('error', "Le compte LDAP « {$uid} » est déjà importé dans le panel.");
        }

        try {
            $ldapUser = collect(app(LdapService::class)->listUsers())->firstWhere('uid', $uid);
        } catch (\Throwable $e) {
            return redirect()->route('admin.ldap.import.index')
                ->with('error', 'Impossible de récupérer les infos LDAP : ' . $e->getMessage());
        }

        if (!$ldapUser) {
            return redirect()->route('admin.ldap.import.index')
                ->with('error', "Compte LDAP « {$uid} » introuvable.");
        }

        $clients = User::where('is_admin', false)->where('is_active', true)->orderBy('last_name')->get();
        $plans   = Plan::where('type', 'service')->whereNotNull('ldap_group')->orderBy('name')->get();

        return view('admin.ldap.import-show', compact('ldapUser', 'clients', 'plans'));
    }

    public function importStore(Request $request, string $uid)
    {
        if (User::where('ldap_username', $uid)->exists()) {
            return redirect()->route('admin.ldap.import.index')
                ->with('error', "Le compte LDAP « {$uid} » est déjà importé.");
        }

        $request->validate([
            'user_id'        => 'required|exists:users,id',
            'plan_id'        => 'required|exists:plans,id',
            'billing_period' => 'nullable|in:monthly,yearly',
            'promo_code'     => 'nullable|string',
            'paid_at'        => 'nullable|date',
        ]);

        $client = User::findOrFail($request->user_id);
        $plan   = Plan::findOrFail($request->plan_id);

        if ($client->ldap_username && $client->ldap_username !== $uid) {
            return back()->withErrors(['user_id' => "Ce client a déjà un compte LDAP différent ({$client->ldap_username}) rattaché."])->withInput();
        }

        try {
            $invoice = app(BillingImportService::class)->createPaidInvoice(
                $client, $plan, $request->billing_period, $request->promo_code, $request->paid_at
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['promo_code' => $e->getMessage()])->withInput();
        }

        $client->update([
            'ldap_username' => $uid,
            'ldap_dn'       => 'uid=' . $uid . ',' . Setting::get('ldap_users_dn', ''),
        ]);

        $warning = null;
        try {
            app(ProvisioningService::class)->provisionLdap($client->fresh(), $plan);
        } catch (\Throwable $e) {
            $warning = 'Compte importé et facturation démarrée, mais la synchronisation LDAP/quota a échoué : ' . $e->getMessage();
        }

        $redirect = redirect()->route('admin.ldap.import.index')
            ->with('success', "« {$uid} » importé et rattaché à {$client->full_name} — facture {$invoice->number} créée et facturation récurrente activée.");

        if ($warning) {
            $redirect->with('warning', $warning);
        }

        return $redirect;
    }
}
