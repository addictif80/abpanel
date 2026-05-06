<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'type', 'vm_type', 'description', 'price', 'currency',
        'billing_period', 'stripe_price_id', 'features',
        'cores', 'memory_mb', 'disk_gb', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'features'  => 'array',
            'is_active' => 'boolean',
            'price'     => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('price');
    }

    public function formattedPrice(): string
    {
        return number_format($this->price, 2) . '€/' . ($this->billing_period === 'yearly' ? 'an' : 'mois');
    }
}
