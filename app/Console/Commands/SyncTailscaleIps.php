<?php

namespace App\Console\Commands;

use App\Jobs\SyncVmProxyHostJob;
use App\Models\VirtualMachine;
use App\Services\TailscaleService;
use Illuminate\Console\Command;

class SyncTailscaleIps extends Command
{
    protected $signature   = 'vm:sync-tailscale {--vm= : ID d\'une seule VM à synchroniser}';
    protected $description = 'Synchronise les IPs Tailscale des VMs depuis l\'API Tailscale';

    public function handle(): int
    {
        $tailscale = app(TailscaleService::class);

        if (!$tailscale->isConfigured()) {
            $this->error('Clé API Tailscale non configurée (Paramètres → Tailscale).');
            return self::FAILURE;
        }

        try {
            $devices = $tailscale->getDevices();
        } catch (\Throwable $e) {
            $this->error('Impossible de contacter l\'API Tailscale : ' . $e->getMessage());
            return self::FAILURE;
        }

        $query = VirtualMachine::query();
        if ($vmId = $this->option('vm')) {
            $query->where('id', $vmId);
        }

        $vms     = $query->get();
        $updated = 0;

        foreach ($vms as $vm) {
            $hostname = strtolower($vm->name);

            $device = $devices[$hostname]
                ?? $devices[explode('.', $hostname)[0]]
                ?? null;

            if (!$device) {
                $this->line("  <fg=gray>— {$vm->name} : aucun device Tailscale trouvé</>");
                continue;
            }

            $ip = null;
            foreach ($device['addresses'] ?? [] as $addr) {
                if (!str_contains($addr, ':')) {
                    $ip = $addr;
                    break;
                }
            }

            if (!$ip) {
                $this->line("  <fg=gray>— {$vm->name} : pas d'IPv4 dans le device Tailscale</>");
                continue;
            }

            if ($vm->tailscale_ip !== $ip) {
                $vm->update(['tailscale_ip' => $ip]);
                if ($vm->subdomain) {
                    SyncVmProxyHostJob::dispatch($vm->id);
                }
                $this->info("  ✓ {$vm->name} → {$ip}");
                $updated++;
            } else {
                $this->line("  <fg=gray>= {$vm->name} : {$ip} (inchangé)</>");
            }
        }

        $this->info("\nTerminé. {$updated} VM(s) mise(s) à jour sur {$vms->count()}.");
        return self::SUCCESS;
    }
}
