<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HostingAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'plan_id', 'cyberpanel_username', 'domain',
        'plan', 'disk_mb', 'is_active',
        'monthly_price', 'next_renewal_at',
        'cancellation_code', 'cancellation_code_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active'                    => 'boolean',
            'monthly_price'                => 'decimal:2',
            'next_renewal_at'              => 'datetime',
            'cancellation_code_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function planCatalog(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}
