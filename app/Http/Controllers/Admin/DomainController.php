<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientDomain;
use App\Services\NginxProxyManagerService;

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
}
