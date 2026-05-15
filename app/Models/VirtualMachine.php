<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualMachine extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'name', 'proxmox_vmid', 'proxmox_node', 'vm_type',
        'status', 'root_password', 'provisioning_status', 'provisioning_error',
        'cores', 'memory_mb', 'swap_mb', 'disk_gb', 'disk_storage',
        'os_template', 'ip_address', 'tailscale_ip',
        'subdomain', 'custom_domain', 'domain_active',
        'plan', 'monthly_price', 'next_renewal_at',
        'cancellation_code', 'cancellation_code_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'domain_active'                  => 'boolean',
            'monthly_price'                  => 'decimal:2',
            'next_renewal_at'                => 'datetime',
            'root_password'                  => 'encrypted',
            'cancellation_code_expires_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isHibernated(): bool
    {
        return $this->status === 'hibernated';
    }
}
