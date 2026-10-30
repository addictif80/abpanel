<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A configured Proxmox / CyberPanel / Synology server. Credentials live in
 * `config` (encrypted as a whole JSON blob) so the shape can differ per
 * type without a wide, mostly-null column set. Exactly one server per type
 * may be is_default=true — enforced in Admin\InfraServerController, not at
 * the DB level, since SQLite/MySQL partial unique indexes aren't portable
 * here.
 */
class InfraServer extends Model
{
    protected $fillable = ['type', 'label', 'config', 'is_default'];

    protected function casts(): array
    {
        return [
            'config'     => 'encrypted:array',
            'is_default' => 'boolean',
        ];
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function virtualMachines(): HasMany
    {
        return $this->hasMany(VirtualMachine::class, 'proxmox_server_id');
    }

    public function hostingAccounts(): HasMany
    {
        return $this->hasMany(HostingAccount::class, 'cyberpanel_server_id');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}
