<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InfraServer;
use App\Services\CyberPanelService;
use App\Services\ProxmoxService;
use App\Services\SynologyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InfraServerController extends Controller
{
    private const TYPES = ['proxmox', 'cyberpanel', 'synology'];

    public function index()
    {
        $servers = InfraServer::orderBy('type')->orderByDesc('is_default')->orderBy('label')->get()->groupBy('type');

        return view('admin.infra-servers.index', compact('servers'));
    }

    public function create(Request $request)
    {
        $type = $request->query('type');
        abort_unless(in_array($type, self::TYPES, true), 404);

        return view('admin.infra-servers.form', compact('type'));
    }

    public function store(Request $request)
    {
        $type = $request->input('type');
        abort_unless(in_array($type, self::TYPES, true), 404);

        $request->validate($this->rulesFor($type));

        DB::transaction(function () use ($request, $type) {
            if ($request->boolean('is_default')) {
                InfraServer::ofType($type)->update(['is_default' => false]);
            }

            InfraServer::create([
                'type'       => $type,
                'label'      => $request->label,
                'config'     => $this->configFor($type, $request),
                'is_default' => $request->boolean('is_default') || !InfraServer::ofType($type)->exists(),
            ]);
        });

        return redirect()->route('admin.infra-servers.index')->with('success', 'Serveur ajouté.');
    }

    public function edit(InfraServer $infraServer)
    {
        return view('admin.infra-servers.form', ['server' => $infraServer]);
    }

    public function update(Request $request, InfraServer $infraServer)
    {
        $request->validate($this->rulesFor($infraServer->type, forUpdate: true));

        DB::transaction(function () use ($request, $infraServer) {
            if ($request->boolean('is_default')) {
                InfraServer::ofType($infraServer->type)->where('id', '!=', $infraServer->id)->update(['is_default' => false]);
            }

            $config = $this->configFor($infraServer->type, $request, $infraServer);

            $infraServer->update([
                'label'      => $request->label,
                'config'     => $config,
                'is_default' => $request->boolean('is_default'),
            ]);
        });

        return redirect()->route('admin.infra-servers.index')->with('success', 'Serveur mis à jour.');
    }

    public function destroy(InfraServer $infraServer)
    {
        $inUse = match ($infraServer->type) {
            'proxmox'    => $infraServer->virtualMachines()->exists()
                || \App\Models\OsTemplate::where('proxmox_server_id', $infraServer->id)->exists()
                || \App\Models\Plan::where('proxmox_server_id', $infraServer->id)->exists(),
            'cyberpanel' => $infraServer->hostingAccounts()->exists()
                || \App\Models\User::where('cyberpanel_server_id', $infraServer->id)->exists()
                || \App\Models\Plan::where('cyberpanel_server_id', $infraServer->id)->exists(),
            'synology'   => \App\Models\Plan::where('synology_server_id', $infraServer->id)->exists(),
            default      => false,
        };

        if ($inUse) {
            return back()->with('error', 'Impossible de supprimer ce serveur : des ressources (VMs, hébergements, plans...) y sont rattachées.');
        }

        $infraServer->delete();

        return redirect()->route('admin.infra-servers.index')->with('success', 'Serveur supprimé.');
    }

    public function test(InfraServer $infraServer)
    {
        try {
            $result = match ($infraServer->type) {
                'proxmox'    => (new ProxmoxService($infraServer))->testConnection(),
                'cyberpanel' => (new CyberPanelService($infraServer))->testConnection(),
                'synology'   => (new SynologyService($infraServer))->testConnection(),
                default      => false,
            };

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Connexion réussie ✓' : 'Connexion échouée',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
    }

    private function rulesFor(string $type, bool $forUpdate = false): array
    {
        $passwordRule = $forUpdate ? 'nullable|string' : 'required|string';

        return match ($type) {
            'proxmox' => [
                'label'            => 'required|string|max:100',
                'host'             => 'required|url',
                'user'             => 'required|string|max:100',
                'password'         => $passwordRule,
                'realm'            => 'required|string|max:50',
                'default_node'     => 'nullable|string|max:100',
                'default_storage'  => 'nullable|string|max:100',
                'is_default'       => 'nullable|boolean',
            ],
            'cyberpanel' => [
                'label'        => 'required|string|max:100',
                'host'         => 'required|url',
                'user'         => 'required|string|max:100',
                'api_token'    => $passwordRule,
                'tailscale_ip' => 'nullable|ip',
                'is_default'   => 'nullable|boolean',
            ],
            'synology' => [
                'label'         => 'required|string|max:100',
                'host'          => 'required|url',
                'user'          => 'required|string|max:100',
                'password'      => $passwordRule,
                'shared_folder' => 'nullable|string|max:100',
                'is_default'    => 'nullable|boolean',
            ],
            default => [],
        };
    }

    private function configFor(string $type, Request $request, ?InfraServer $existing = null): array
    {
        $keep = fn(string $key) => $request->filled($key) ? $request->input($key) : ($existing?->get($key) ?? '');

        return match ($type) {
            'proxmox' => [
                'host'            => $request->host,
                'user'            => $request->user,
                'password'        => $keep('password'),
                'realm'           => $request->realm,
                'default_node'    => $request->default_node ?? '',
                'default_storage' => $request->default_storage ?? '',
            ],
            'cyberpanel' => [
                'host'         => $request->host,
                'user'         => $request->user,
                // Strip an accidentally pasted "Basic " prefix (CyberPanel displays the
                // full Authorization header value, not just the bare token).
                'api_token'    => preg_replace('/^Basic\s+/i', '', trim($keep('api_token'))),
                'tailscale_ip' => $request->tailscale_ip ?? '',
            ],
            'synology' => [
                'host'          => $request->host,
                'user'          => $request->user,
                'password'      => $keep('password'),
                'shared_folder' => $request->shared_folder ?? '',
            ],
            default => [],
        };
    }
}
