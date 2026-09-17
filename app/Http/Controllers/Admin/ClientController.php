<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Services\DataExportService;
use App\Services\MailService;
use App\Services\NginxProxyManagerService;
use App\Services\ProxmoxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = User::where('is_admin', false)
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('email', 'like', "%{$request->search}%")
                  ->orWhere('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name', 'like', "%{$request->search}%");
            }))
            ->withCount(['virtualMachines', 'hostingAccounts', 'tickets'])
            ->latest()
            ->paginate(20);

        return view('admin.clients.index', compact('clients'));
    }

    public function show(User $client)
    {
        $client->load([
            'virtualMachines',
            'hostingAccounts',
            'quotes'        => fn($q) => $q->where('is_template', false)->latest()->limit(10),
            'invoices'      => fn($q) => $q->latest()->limit(10),
            'creditNotes'   => fn($q) => $q->latest()->limit(10),
            'tickets'       => fn($q) => $q->latest()->limit(10),
        ]);
        return view('admin.clients.show', compact('client'));
    }

    public function create()
    {
        return view('admin.clients.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:8',
            'phone'      => 'nullable|string|max:20',
            'company'    => 'nullable|string|max:100',
        ]);

        $client = User::create([
            'name'       => $request->first_name . ' ' . $request->last_name,
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'phone'      => $request->phone,
            'company'    => $request->company,
            'is_admin'   => false,
            'is_active'  => true,
        ]);

        try {
            app(MailService::class)->sendFromTemplate('welcome', $client->email, [
                'first_name' => $client->first_name,
                'last_name'  => $client->last_name,
                'email'      => $client->email,
                'login_url'  => route('login'),
            ]);
        } catch (\Exception) {
            // Non-blocking
        }

        return redirect()->route('admin.clients.show', $client)->with('success', 'Client créé avec succès.');
    }

    public function edit(User $client)
    {
        return view('admin.clients.edit', compact('client'));
    }

    public function update(Request $request, User $client)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email,' . $client->id,
            'phone'      => 'nullable|string|max:20',
            'company'    => 'nullable|string|max:100',
            'is_active'  => 'boolean',
        ]);

        $client->update([
            'name'                => $request->first_name . ' ' . $request->last_name,
            'first_name'          => $request->first_name,
            'last_name'           => $request->last_name,
            'email'               => $request->email,
            'phone'               => $request->phone,
            'company'             => $request->company,
            'cyberpanel_username' => $request->cyberpanel_username,
            'is_active'           => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Client mis à jour.');
    }

    public function destroy(User $client)
    {
        // Invoices/credit notes are accounting records that must survive — never
        // delete a client that has any, even indirectly via cascade. Anonymizing
        // (below) is the GDPR-compliant way to retire such a client instead.
        if ($client->invoices()->exists() || $client->creditNotes()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce client : il possède des factures ou avoirs (historique comptable à conserver). Utilisez "Anonymiser" à la place.');
        }

        if ($error = $this->deprovisionResources($client)) {
            return back()->with('error', $error);
        }

        $client->delete();

        return redirect()->route('admin.clients.index')->with('success', 'Client et ses ressources associées (VMs, hébergements, domaines) supprimés.');
    }

    /**
     * GDPR-compliant alternative to destroy() for a client with billing
     * history: deprovisions live resources exactly like destroy(), then
     * strips all personal data from the User row instead of deleting it, so
     * invoices/credit notes/quotes/tickets keep a valid (if anonymous)
     * owner. Past invoices are unaffected — they keep their own frozen
     * billing_snapshot regardless of what happens to the live profile.
     */
    public function anonymize(User $client)
    {
        if ($client->is_admin) {
            return back()->with('error', "Impossible d'anonymiser un compte administrateur.");
        }

        if ($client->anonymized_at) {
            return back()->with('error', 'Ce client est déjà anonymisé.');
        }

        if ($error = $this->deprovisionResources($client)) {
            return back()->with('error', $error);
        }

        $placeholder = 'suppr_' . (Str::slug($client->full_name) ?: $client->id) . '_' . $client->id;

        $client->update([
            'name'                  => $placeholder,
            'first_name'            => 'Client',
            'last_name'             => 'supprimé',
            'email'                 => "{$placeholder}@anonymise.local",
            'password'              => Hash::make(Str::random(40)),
            'phone'                 => null,
            'company'               => null,
            'address'               => null,
            'city'                  => null,
            'zip'                   => null,
            'siret'                 => null,
            'vat_number'            => null,
            'cyberpanel_username'   => null,
            'cyberpanel_password'   => null,
            'ldap_username'         => null,
            'ldap_password'         => null,
            'ldap_dn'               => null,
            'ldap_group'            => null,
            'stripe_customer_id'    => null,
            'newsletter_subscribed' => false,
            'is_active'             => false,
            'anonymized_at'         => now(),
        ]);

        NewsletterSubscriber::where('user_id', $client->id)->update([
            'email'          => "{$placeholder}@anonymise.local",
            'first_name'     => null,
            'last_name'      => null,
            'status'         => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);

        return redirect()->route('admin.clients.index')
            ->with('success', "Client anonymisé (« {$placeholder} »). L'historique de facturation est conservé.");
    }

    /** @return string|null an error message if deprovisioning failed, null on success */
    private function deprovisionResources(User $client): ?string
    {
        foreach ($client->virtualMachines as $vm) {
            try {
                app(ProxmoxService::class)->deleteInstance($vm->proxmox_node, (int) $vm->proxmox_vmid, $vm->vm_type ?? 'qemu');
            } catch (\Throwable $e) {
                return "Échec de la résiliation de la VM « {$vm->name} » sur Proxmox : {$e->getMessage()}. Résiliez-la manuellement (page VM) avant de continuer.";
            }
            $vm->delete();
        }

        foreach ($client->clientDomains as $domain) {
            if ($domain->npm_proxy_id) {
                try {
                    app(NginxProxyManagerService::class)->deleteProxyHost($domain->npm_proxy_id);
                } catch (\Throwable) {}
            }
            $domain->delete();
        }

        $client->hostingAccounts()->delete();

        return null;
    }

    public function impersonate(User $client)
    {
        if ($client->is_admin) {
            return back()->with('error', 'Impossible d\'impersonner un administrateur.');
        }

        session(['impersonating_admin_id' => auth()->id()]);
        auth()->login($client);

        return redirect()->route('client.dashboard')->with('success', 'Vous consultez l\'espace de ' . $client->full_name . '.');
    }

    public function stopImpersonating()
    {
        $adminId = session('impersonating_admin_id');

        if (!$adminId) {
            return redirect()->route('admin.dashboard');
        }

        session()->forget('impersonating_admin_id');
        auth()->loginUsingId($adminId);

        return redirect()->route('admin.dashboard')->with('success', 'Vous êtes de retour sur votre compte administrateur.');
    }

    public function resetPassword(Request $request, User $client)
    {
        $request->validate(['password' => 'required|string|min:8']);

        // UserObserver::updated() already syncs the CyberPanel password
        // automatically whenever `password` changes and cyberpanel_username is
        // set — calling CyberPanelService here too would just duplicate that
        // API call and produce a misleading success/error message.
        $client->update(['password' => Hash::make($request->password)]);

        $msg = 'Mot de passe réinitialisé.';
        if ($client->cyberpanel_username) {
            $msg .= ' Synchronisation CyberPanel lancée (voir les logs en cas d\'échec).';
        }

        return back()->with('success', $msg);
    }

    public function resendWelcome(User $client)
    {
        try {
            app(MailService::class)->sendFromTemplate('welcome', $client->email, [
                'first_name' => $client->first_name,
                'last_name'  => $client->last_name,
                'email'      => $client->email,
                'login_url'  => route('login'),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', "Échec de l'envoi : " . $e->getMessage());
        }

        return back()->with('success', 'Mail de bienvenue renvoyé à ' . $client->email . '.');
    }

    /** Fulfills a client's GDPR access/portability request (Art. 15 & 20) on their behalf. */
    public function exportData(User $client, DataExportService $export)
    {
        $data = $export->export($client);

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="donnees-' . $client->id . '-' . now()->format('Y-m-d') . '.json"',
        ], JSON_PRETTY_PRINT);
    }
}
