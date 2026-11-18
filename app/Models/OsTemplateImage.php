<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OsTemplateImage extends Model
{
    protected $fillable = [
        'os_template_id', 'proxmox_node', 'volume', 'download_upid',
        'active_deployments', 'last_used_at',
    ];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime'];
    }

    public function osTemplate(): BelongsTo
    {
        return $this->belongsTo(OsTemplate::class);
    }
}
