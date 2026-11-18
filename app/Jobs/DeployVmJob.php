<?php

namespace App\Jobs;

use App\Models\OsTemplate;
use App\Models\OsTemplateImage;
use App\Models\Setting;
use App\Models\VirtualMachine;
use App\Services\ProvisioningService;
use App\Services\ProxmoxService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Deploys a QEMU VM from a cloud image (OsTemplate of type 'qemu') that is
 * downloaded on the fly to the node and removed afterwards, so node storage is
 * not permanently used by OS images.
 *
 * Runs as a small state machine: every step re-dispatches the job with a delay
 * instead of blocking a worker (the database queue's retry_after would
 * otherwise re-run long jobs). Steps:
 *
 *   [destroy → wait_destroy]  (reinstall only)
 *   prepare → [download] → create → wait_create → configure
 *
 * Concurrent deployments of the same image on the same node share a single
 * download through OsTemplateImage (lease counter + download UPID).
 */
class DeployVmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    private const POLL_DELAY_SECONDS = 8;
    private const MAX_POLLS          = 450; // ~1 h
    private const MAX_ERRORS         = 5;

    /**
     * @param array $state Carried between steps: upid, polls, errors, leased, notify
     */
    public function __construct(
        public int $vmId,
        public int $osTemplateId,
        public string $step = 'prepare',
        public array $state = [],
    ) {}

    /** Entry point used by callers. $reinstall = delete the existing VM first. */
    public static function start(VirtualMachine $vm, OsTemplate $template, bool $reinstall = false, bool $notify = false): void
    {
        $vm->update(['provisioning_status' => 'provisioning', 'provisioning_error' => null]);

        self::dispatch($vm->id, $template->id, $reinstall ? 'destroy' : 'prepare', ['notify' => $notify]);
    }

    public function handle(): void
    {
        $vm       = VirtualMachine::find($this->vmId);
        $template = OsTemplate::find($this->osTemplateId);

        if (!$vm || !$template) {
            $this->release(null);
            return;
        }

        try {
            $next = match ($this->step) {
                'destroy'      => $this->stepDestroy($vm),
                'wait_destroy' => $this->stepWaitDestroy($vm),
                'prepare'      => $this->stepPrepare($vm, $template),
                'download'     => $this->stepDownload($vm, $template),
                'create'       => $this->stepCreate($vm, $template),
                'wait_create'  => $this->stepWaitCreate($vm),
                'configure'    => $this->stepConfigure($vm, $template),
                default        => throw new \RuntimeException("Étape inconnue : {$this->step}"),
            };
        } catch (\Throwable $e) {
            $this->fail($vm, $template, $e);
            return;
        }

        if ($next === null) {
            return; // finished
        }

        [$step, $state, $delay] = $next;
        self::dispatch($this->vmId, $this->osTemplateId, $step, $state)->delay(now()->addSeconds($delay));
    }

    // ── Steps — each returns [nextStep, state, delaySeconds] or null when done ──

    private function stepDestroy(VirtualMachine $vm): array
    {
        $proxmox = $vm->proxmoxService();
        $vmid    = (int) $vm->proxmox_vmid;

        if ($vmid <= 0 || !$proxmox->vmExists($vm->proxmox_node, $vmid)) {
            return ['prepare', $this->state, 0];
        }

        $status = $proxmox->getStatus($vm->proxmox_node, $vmid)['status'] ?? '';
        if ($status !== 'stopped') {
            $proxmox->action($vm->proxmox_node, $vmid, 'stop');
            return $this->poll('destroy', 5);
        }

        $upid = $proxmox->deleteVMTask($vm->proxmox_node, $vmid);
        return ['wait_destroy', $this->withState(['upid' => $upid, 'polls' => 0]), self::POLL_DELAY_SECONDS];
    }

    private function stepWaitDestroy(VirtualMachine $vm): array
    {
        $result = $this->taskResult($vm, $this->state['upid'] ?? '');
        if ($result === 'running') {
            return $this->poll('wait_destroy');
        }
        if ($result !== 'ok') {
            throw new \RuntimeException("Suppression de l'ancienne VM échouée : {$result}");
        }
        return ['prepare', $this->withState(['upid' => null, 'polls' => 0]), 0];
    }

    private function stepPrepare(VirtualMachine $vm, OsTemplate $template): array
    {
        $proxmox = $vm->proxmoxService();
        $node    = $vm->proxmox_node;

        $img = DB::transaction(function () use ($template, $node) {
            $row = OsTemplateImage::firstOrCreate(['os_template_id' => $template->id, 'proxmox_node' => $node]);
            $row = OsTemplateImage::whereKey($row->id)->lockForUpdate()->first();
            if (empty($this->state['leased'])) {
                $row->increment('active_deployments');
            }
            return $row->fresh();
        });
        $this->state['leased'] = true;

        // Image already on the node (kept by retention, or another deployment just fetched it)
        if ($img->volume && $this->imageOnNode($proxmox, $node, $template, $img->volume)) {
            return ['create', $this->withState(['polls' => 0]), 0];
        }
        if ($img->volume) {
            $img->update(['volume' => null]); // was purged behind our back
        }

        if ($img->download_upid) {
            return ['download', $this->withState(['polls' => 0]), self::POLL_DELAY_SECONDS];
        }

        $existing = $this->findVolume($proxmox, $node, $template);
        if ($existing) {
            $img->update(['volume' => $existing]);
            return ['create', $this->withState(['polls' => 0]), 0];
        }

        $upid = $proxmox->downloadTemplate(
            $node, $template->proxmox_storage, $template->url, $template->filename, 'import',
            $template->checksum, $template->checksum_algorithm,
        );
        $img->update(['download_upid' => $upid]);

        return ['download', $this->withState(['polls' => 0]), self::POLL_DELAY_SECONDS];
    }

    private function stepDownload(VirtualMachine $vm, OsTemplate $template): array
    {
        $proxmox = $vm->proxmoxService();
        $node    = $vm->proxmox_node;
        $img     = OsTemplateImage::where('os_template_id', $template->id)->where('proxmox_node', $node)->firstOrFail();

        if (!$img->download_upid) {
            // Someone else finished (or failed) the download meanwhile.
            if ($img->volume) {
                return ['create', $this->withState(['polls' => 0]), 0];
            }
            throw new \RuntimeException("Téléchargement de l'image échoué (autre déploiement).");
        }

        $result = $this->taskResult($vm, $img->download_upid);
        if ($result === 'running') {
            return $this->poll('download');
        }

        $img->update(['download_upid' => null]);
        if ($result !== 'ok') {
            throw new \RuntimeException("Téléchargement de l'image échoué : {$result}");
        }

        $volume = $this->findVolume($proxmox, $node, $template)
            ?? "{$template->proxmox_storage}:import/{$template->filename}";
        $img->update(['volume' => $volume]);

        return ['create', $this->withState(['polls' => 0]), 0];
    }

    private function stepCreate(VirtualMachine $vm, OsTemplate $template): array
    {
        $proxmox = $vm->proxmoxService();
        $node    = $vm->proxmox_node;
        $img     = OsTemplateImage::where('os_template_id', $template->id)->where('proxmox_node', $node)->firstOrFail();

        if (!$img->volume) {
            throw new \RuntimeException('Image introuvable sur le nœud.');
        }

        $vmid = (int) $vm->proxmox_vmid ?: $proxmox->getNextVMID();
        $vm->update(['proxmox_vmid' => $vmid]);

        $storage = $vm->disk_storage;
        $config  = [
            'vmid'       => $vmid,
            'name'       => $vm->name,
            'cores'      => $vm->cores,
            'memory'     => $vm->memory_mb,
            'scsihw'     => 'virtio-scsi-pci',
            'scsi0'      => "{$storage}:0,import-from={$img->volume}",
            'ide2'       => "{$storage}:cloudinit",
            'boot'       => 'order=scsi0',
            'net0'       => 'virtio,bridge=vmbr0',
            'ostype'     => 'l26',
            'agent'      => 1,
            'serial0'    => 'socket',
            'ciuser'     => $template->ci_user ?: 'root',
            'cipassword' => $vm->root_password,
            'ipconfig0'  => 'ip=dhcp',
        ];

        $upid = $proxmox->createVMTask($node, $config);

        return ['wait_create', $this->withState(['upid' => $upid, 'polls' => 0]), self::POLL_DELAY_SECONDS];
    }

    private function stepWaitCreate(VirtualMachine $vm): array
    {
        $result = $this->taskResult($vm, $this->state['upid'] ?? '');
        if ($result === 'running') {
            return $this->poll('wait_create');
        }
        if ($result !== 'ok') {
            throw new \RuntimeException("Création de la VM échouée : {$result}");
        }
        return ['configure', $this->withState(['upid' => null, 'polls' => 0]), 0];
    }

    private function stepConfigure(VirtualMachine $vm, OsTemplate $template): ?array
    {
        $proxmox = $vm->proxmoxService();
        $node    = $vm->proxmox_node;
        $vmid    = (int) $vm->proxmox_vmid;

        // Grow the imported disk to the plan size (it can't shrink below the image size).
        try {
            $proxmox->resizeDisk($node, $vmid, 'scsi0', "{$vm->disk_gb}G");
        } catch (\Throwable $e) {
            Log::warning("DeployVmJob: resize failed for VM {$vm->id}: " . $e->getMessage());
        }

        $proxmox->action($node, $vmid, 'start');

        $subdomain = $vm->subdomain;
        if (!$subdomain && ($base = Setting::get('vms_base_domain'))) {
            $subdomain = "vm{$vmid}.{$base}";
        }

        $vm->update([
            'status'              => 'running',
            'provisioning_status' => 'active',
            'provisioning_error'  => null,
            'os_template'         => $template->name,
            'subdomain'           => $subdomain,
        ]);

        if ($subdomain) {
            SyncVmProxyHostJob::dispatch($vm->id);
        }
        JoinTailscaleJob::dispatch($vm->id)->delay(now()->addSeconds(60));

        if (!empty($this->state['notify']) && $vm->plan_id && $vm->user && ($plan = $vm->planModel())) {
            app(ProvisioningService::class)->sendProvisioningEmail($vm->user, $vm, $plan, (string) $vm->root_password);
        }

        $this->release($template);
        return null;
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /** Returns 'running', 'ok', or the failure message. Tolerates a few transient API errors. */
    private function taskResult(VirtualMachine $vm, string $upid): string
    {
        if ($upid === '') {
            return 'UPID manquant';
        }
        try {
            $task = $vm->proxmoxService()->getTaskStatus($vm->proxmox_node, $upid);
        } catch (\Throwable $e) {
            $this->state['errors'] = ($this->state['errors'] ?? 0) + 1;
            if ($this->state['errors'] >= self::MAX_ERRORS) {
                throw $e;
            }
            return 'running';
        }

        if (($task['status'] ?? 'running') !== 'stopped') {
            return 'running';
        }
        return ($task['exitstatus'] ?? '') === 'OK' ? 'ok' : (string) ($task['exitstatus'] ?? 'échec inconnu');
    }

    private function poll(string $step, int $delay = self::POLL_DELAY_SECONDS): array
    {
        $polls = ($this->state['polls'] ?? 0) + 1;
        if ($polls > self::MAX_POLLS) {
            throw new \RuntimeException("Délai dépassé à l'étape « {$step} ».");
        }
        return [$step, $this->withState(['polls' => $polls]), $delay];
    }

    private function withState(array $changes): array
    {
        return array_merge($this->state, $changes);
    }

    private function findVolume(ProxmoxService $proxmox, string $node, OsTemplate $template): ?string
    {
        try {
            foreach ($proxmox->listStorageContent($node, $template->proxmox_storage, 'import') as $item) {
                if (str_ends_with((string) ($item['volid'] ?? ''), '/' . $template->filename)) {
                    return $item['volid'];
                }
            }
        } catch (\Throwable) {
        }
        return null;
    }

    private function imageOnNode(ProxmoxService $proxmox, string $node, OsTemplate $template, string $volume): bool
    {
        return $this->findVolume($proxmox, $node, $template) === $volume;
    }

    /**
     * Drop this deployment's lease. When nobody uses the image any more and the
     * template has no retention, remove it from the node right away; otherwise
     * `os-images:purge` removes it once the retention delay has passed.
     */
    private function release(?OsTemplate $template): void
    {
        if (empty($this->state['leased']) || !$template) {
            return;
        }
        $vm = VirtualMachine::find($this->vmId);
        if (!$vm) {
            return;
        }

        $img = OsTemplateImage::where('os_template_id', $template->id)->where('proxmox_node', $vm->proxmox_node)->first();
        if (!$img) {
            return;
        }

        $img = DB::transaction(function () use ($img) {
            $row = OsTemplateImage::whereKey($img->id)->lockForUpdate()->first();
            $row->update([
                'active_deployments' => max(0, $row->active_deployments - 1),
                'last_used_at'       => now(),
            ]);
            return $row->fresh();
        });

        if ($img->active_deployments === 0 && $img->volume && !$img->download_upid && $template->retention_minutes <= 0) {
            self::deleteImage($img, $template);
        }
    }

    public static function deleteImage(OsTemplateImage $img, OsTemplate $template): void
    {
        try {
            $server = $template->proxmoxServer;
            (new ProxmoxService($server))->deleteStorageContent($img->proxmox_node, $template->proxmox_storage, $img->volume);
            $img->update(['volume' => null]);
        } catch (\Throwable $e) {
            Log::warning("DeployVmJob: could not remove image {$img->volume} from {$img->proxmox_node}: " . $e->getMessage());
        }
    }

    private function fail(VirtualMachine $vm, ?OsTemplate $template, \Throwable $e): void
    {
        Log::error("DeployVmJob failed for VM {$vm->id} at step {$this->step}: " . $e->getMessage());

        $vm->update([
            'provisioning_status' => 'failed',
            'provisioning_error'  => $e->getMessage(),
        ]);
        Log::error("ACTION REQUIRED: VM deployment failed for VM {$vm->id} ({$vm->name}) — {$e->getMessage()}");

        $this->release($template);
    }

    public function failed(\Throwable $e): void
    {
        // Worker crash / timeout: mark the VM failed so it doesn't stay "provisioning" forever.
        $vm = VirtualMachine::find($this->vmId);
        if ($vm && $vm->provisioning_status === 'provisioning') {
            $this->fail($vm, OsTemplate::find($this->osTemplateId), $e);
        }
    }
}
