<?php

namespace App\Services;

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
        private NginxProxyManagerService $npm,
        private MailService $mail,
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

        if ($plan->type !== 'vm') {
            // Hosting plans provisioned differently (CyberPanel) — not yet implemented
            return;
        }

        $this->provisionVm($user, $plan, $invoice);
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

            // NPM subdomain
            $baseDomain = Setting::get('vms_base_domain');
            $subdomain  = $baseDomain ? "vm{$vmid}.{$baseDomain}" : null;
            if ($subdomain) {
                try {
                    $this->npm->createProxyHost($subdomain, '');
                } catch (\Exception) {}
            }

            $vm->update([
                'proxmox_vmid'        => $vmid,
                'root_password'       => $password,
                'provisioning_status' => 'active',
                'subdomain'           => $subdomain,
                'status'              => $isLxc ? 'stopped' : 'stopped',
            ]);

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
        // 16 chars: letters + digits + special (shell-safe)
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%&*';
        return substr(str_shuffle(str_repeat($chars, 4)), 0, 16);
    }
}
