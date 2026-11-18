<?php

namespace App\Console\Commands;

use App\Jobs\DeployVmJob;
use App\Models\OsTemplateImage;
use Illuminate\Console\Command;

class PurgeCloudImages extends Command
{
    protected $signature = 'os-images:purge';
    protected $description = 'Remove cloud images kept on Proxmox nodes once their retention delay has passed';

    public function handle(): int
    {
        // Safety net: a lease that was never released (worker crash) would pin the image forever.
        OsTemplateImage::where('active_deployments', '>', 0)
            ->where('updated_at', '<', now()->subHours(3))
            ->update(['active_deployments' => 0, 'download_upid' => null]);

        $purged = 0;

        OsTemplateImage::with('osTemplate.proxmoxServer')
            ->whereNotNull('volume')
            ->where('active_deployments', 0)
            ->whereNull('download_upid')
            ->get()
            ->each(function (OsTemplateImage $img) use (&$purged) {
                $tpl = $img->osTemplate;
                if (!$tpl) {
                    return;
                }
                $keepUntil = ($img->last_used_at ?? $img->updated_at)->copy()->addMinutes((int) $tpl->retention_minutes);
                if ($keepUntil->isFuture()) {
                    return;
                }
                DeployVmJob::deleteImage($img, $tpl);
                $purged++;
            });

        $this->info("Purged {$purged} cloud image(s).");
        return self::SUCCESS;
    }
}
