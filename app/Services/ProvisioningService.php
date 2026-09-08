<?php

namespace App\Services;

use App\Jobs\ProvisionHostingProxyJob;
use App\Jobs\SyncVmProxyHostJob;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Models\VirtualMachine;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProvisioningService
{
    public function __construct(
        private ProxmoxService $proxmox,
        private MailService $mail,
        private CyberPanelService $cyberPanel,
    ) {}

    /**
     * Provision a VM or container after successful payment.
     * Called from the Stripe webhook.
     */
    public function provisionFromInvoice(Invoice $invoice): void
    {
        $user = $invoice->user;
        $plan = $invoice->plan;

        if (!$plan || !$user) {
            Log::warning("ProvisioningService: missing plan or user for invoice {$invoice->id}");
            return;
        }

        if ($plan->type === 'hosting') {
            $this->provisionHosting($user, $plan, $invoice);
            return;
        }

        $this->provisionVm($user, $plan, $invoice);
    }

    public function provisionHosting(User $user, Plan $plan, ?Invoice $invoice = null): void
    {
        $domain  = $invoice?->metadata['domain'] ?? null;
        $package = $plan->cyberpanel_package ?? 'Default';

        if (!$domain) {
            Log::warning("ProvisioningService: no domain for hosting invoice {$invoice?->id}");
            return;
        }

        try {
            // Reuse existing CyberPanel account or create a new one
            if ($user->cyberpanel_username) {
                $username = $user->cyberpanel_username;
                $password = $user->cyberpanel_password;
            } else {
                $username = $this->generateCyberPanelUsername($user);
                $password = $this->generatePassword();
            }

            $this->cyberPanel->createWebsite(
                domain:   $domain,
                username: $username,
                password: $password,
                email:    $user->email,
                fullName: $user->full_name,
                package:  $package,
            );

            // Save credentials on first provisioning
            if (!$user->cyberpanel_username) {
                $user->update([
                    'cyberpanel_username' => $username,
                    'cyberpanel_password' => $password,
                ]);
            }

            ProvisionHostingProxyJob::dispatch($user->id, $domain);

            $panelUrl = Setting::get('cyberpanel_host', '');

            $this->mail->sendFromTemplate('hosting_provisioned', $user->email, [
                'first_name' => $user->first_name ?: $user->name,
                'domain'     => $domain,
                'username'   => $username,
                'password'   => $password,
                'panel_url'  => $panelUrl,
            ]);

        } catch (\Exception $e) {
            Log::error("Hosting provisioning failed for user {$user->id}, domain {$domain}: " . $e->getMessage());
        }
    }

    public function provisionVm(User $user, Plan $plan, ?Invoice $invoice = null): VirtualMachine
    {
        $node    = Setting::get('proxmox_node', 'pve');
        $storage = Setting::get('proxmox_default_storage', 'local-lvm');
        $isLxc   = ($plan->vm_type ?? 'qemu') === 'lxc';

        // Create a placeholder VM record immediately so the client can see it
        $vm = VirtualMachine::create([
            'user_id'              => $user->id,
            'plan_id'              => $plan->id,
            'name'                 => $this->generateVmName($plan, $user),
            'proxmox_vmid'         => 0,
            'proxmox_node'         => $node,
            'vm_type'              => $plan->vm_type ?? 'qemu',
            'status'               => 'stopped',
            'provisioning_status'  => 'provisioning',
            'cores'                => $plan->cores ?? 1,
            'memory_mb'            => $plan->memory_mb ?? 1024,
            'disk_gb'              => $plan->disk_gb ?? 20,
            'disk_storage'         => $storage,
            'monthly_price'        => $plan->price,
        ]);

        try {
            $password = $this->generatePassword();
            $vmid     = $this->proxmox->getNextVMID();

            if ($isLxc) {
                $this->createLxcInstance($node, $vmid, $vm, $plan, $storage, $password);
            } else {
                $this->createQemuInstance($node, $vmid, $vm, $plan, $storage);
            }

            // Set root password
            try {
                $this->proxmox->setRootPassword($node, $vmid, $password, $plan->vm_type ?? 'qemu');
            } catch (\Exception $e) {
                // For QEMU: guest agent may not be running yet — password was set at cloud-init or LXC config
                Log::warning("Could not set root password via API for VMID {$vmid}: " . $e->getMessage());
            }

            $baseDomain = Setting::get('vms_base_domain');
            $subdomain  = $baseDomain ? "vm{$vmid}.{$baseDomain}" : null;

            $vm->update([
                'proxmox_vmid'        => $vmid,
                'root_password'       => $password,
                'provisioning_status' => 'active',
                'subdomain'           => $subdomain,
                'status'              => $isLxc ? 'stopped' : 'stopped',
            ]);

            // Create the NPM proxy host asynchronously (retries on failure). It will
            // point nowhere until the VM is started and joins Tailscale — see
            // JoinTailscaleJob, dispatched from Client\VmController::start().
            if ($subdomain) {
                SyncVmProxyHostJob::dispatch($vm->id);
            }

            $this->sendProvisioningEmail($user, $vm, $plan, $password);

        } catch (\Exception $e) {
            Log::error("Provisioning failed for user {$user->id}, plan {$plan->id}: " . $e->getMessage());

            $vm->update([
                'provisioning_status' => 'failed',
                'provisioning_error'  => $e->getMessage(),
            ]);

            // Notify admin
            Log::error("ACTION REQUIRED: VM provisioning failed for {$user->email} — {$e->getMessage()}");
        }

        return $vm->fresh();
    }

    private function createQemuInstance(string $node, int $vmid, VirtualMachine $vm, Plan $plan, string $storage): void
    {
        $this->proxmox->createVM($node, [
            'vmid'    => $vmid,
            'name'    => $vm->name,
            'cores'   => $plan->cores ?? 1,
            'memory'  => $plan->memory_mb ?? 1024,
            'scsihw'  => 'virtio-scsi-pci',
            'scsi0'   => "{$storage}:" . ($plan->disk_gb ?? 20),
            'boot'    => 'order=scsi0',
            'net0'    => 'virtio,bridge=vmbr0',
            'ostype'  => 'l26',
        ]);
    }

    private function createLxcInstance(string $node, int $vmid, VirtualMachine $vm, Plan $plan, string $storage, string $password): void
    {
        // Find first available active LXC template
        $template = \App\Models\OsTemplate::where('template_type', 'ct')
            ->where('status', 'ready')
            ->where('is_active', true)
            ->first();

        if (!$template?->proxmox_volume) {
            throw new \RuntimeException('Aucun template LXC disponible. Configurez un template CT dans « Templates OS ».');
        }

        $this->proxmox->createCT($node, [
            'vmid'         => $vmid,
            'hostname'     => $vm->name,
            'ostemplate'   => $template->proxmox_volume,
            'cores'        => $plan->cores ?? 1,
            'memory'       => $plan->memory_mb ?? 1024,
            'swap'         => 512,
            'rootfs'       => "{$storage}:" . ($plan->disk_gb ?? 20),
            'net0'         => 'name=eth0,bridge=vmbr0,ip=dhcp',
            'password'     => $password,
            'unprivileged' => 1,
        ]);
    }

    private function sendProvisioningEmail(User $user, VirtualMachine $vm, Plan $plan, string $password): void
    {
        $typeLabel = ($plan->vm_type ?? 'qemu') === 'lxc' ? 'Conteneur LXC' : 'Machine virtuelle KVM';

        try {
            $this->mail->sendFromTemplate('vm_provisioned', $user->email, [
                'first_name'    => $user->first_name,
                'vm_name'       => $vm->name,
                'type'          => $typeLabel,
                'cores'         => $vm->cores . ' vCPU',
                'memory'        => $vm->memory_mb >= 1024 ? round($vm->memory_mb / 1024, 1) . ' GB' : $vm->memory_mb . ' MB',
                'disk'          => $vm->disk_gb . ' GB',
                'subdomain'     => $vm->subdomain ?: '(à configurer)',
                'root_password' => $password,
                'panel_url'     => route('client.vms.show', $vm),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send provisioning email to {$user->email}: " . $e->getMessage());
        }
    }

    private function generateVmName(Plan $plan, User $user): string
    {
        $prefix = ($plan->vm_type ?? 'qemu') === 'lxc' ? 'ct' : 'vm';
        return $prefix . '-' . Str::slug($user->last_name) . '-' . Str::random(4);
    }

    private function generatePassword(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%&*';
        return substr(str_shuffle(str_repeat($chars, 4)), 0, 16);
    }

    private function generateCyberPanelUsername(User $user): string
    {
        // Sanitize: lowercase alphanumeric only, max 16 chars
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $user->first_name . $user->last_name));
        $base = substr($base ?: 'user', 0, 12);
        // Add random suffix to avoid collisions
        return $base . Str::lower(Str::random(4));
    }
}
