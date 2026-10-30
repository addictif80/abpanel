<?php

use App\Models\InfraServer;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-time migration of the old single-server Proxmox/CyberPanel/Synology
 * Settings into the new infra_servers table, so existing installs keep
 * working exactly as before after upgrading — nothing breaks, nothing
 * needs to be reconfigured by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        $proxmoxHost = Setting::get('proxmox_host');
        if ($proxmoxHost) {
            $server = InfraServer::create([
                'type'  => 'proxmox',
                'label' => 'Serveur principal',
                'config' => [
                    'host'            => $proxmoxHost,
                    'user'            => Setting::get('proxmox_user', 'root'),
                    'password'        => Setting::get('proxmox_password', ''),
                    'realm'           => Setting::get('proxmox_realm', 'pam'),
                    'default_storage' => Setting::get('proxmox_default_storage', ''),
                ],
                'is_default' => true,
            ]);
            DB::table('virtual_machines')->update(['proxmox_server_id' => $server->id]);
            DB::table('os_templates')->update(['proxmox_server_id' => $server->id]);
            DB::table('plans')->where('type', 'vm')->update(['proxmox_server_id' => $server->id]);
        }

        $cyberpanelHost = Setting::get('cyberpanel_host');
        if ($cyberpanelHost) {
            $server = InfraServer::create([
                'type'  => 'cyberpanel',
                'label' => 'Serveur principal',
                'config' => [
                    'host'         => $cyberpanelHost,
                    'user'         => Setting::get('cyberpanel_user', 'admin'),
                    'password'     => Setting::get('cyberpanel_password', ''),
                    'tailscale_ip' => Setting::get('cyberpanel_tailscale_ip', ''),
                ],
                'is_default' => true,
            ]);
            DB::table('hosting_accounts')->update(['cyberpanel_server_id' => $server->id]);
            DB::table('users')->whereNotNull('cyberpanel_username')->update(['cyberpanel_server_id' => $server->id]);
            DB::table('plans')->where('type', 'hosting')->update(['cyberpanel_server_id' => $server->id]);
        }

        $synologyHost = Setting::get('synology_host');
        if ($synologyHost) {
            $server = InfraServer::create([
                'type'  => 'synology',
                'label' => 'Serveur principal',
                'config' => [
                    'host'          => $synologyHost,
                    'user'          => Setting::get('synology_user', ''),
                    'password'      => Setting::get('synology_password', ''),
                    'shared_folder' => Setting::get('synology_cloud_shared_folder', ''),
                ],
                'is_default' => true,
            ]);
            DB::table('plans')->whereNotNull('storage_quota_gb')->update(['synology_server_id' => $server->id]);
        }
    }

    public function down(): void
    {
        // One-way data migration — nothing meaningful to revert.
    }
};
