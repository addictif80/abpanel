<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $fillable = ['code', 'description', 'discount_type', 'discount_value', 'min_amount', 'max_uses', 'used_count', 'expires_at', 'is_active', 'plan_ids'];

    protected function casts(): array
    {
        return [
            'expires_at'     => 'datetime',
            'is_active'      => 'boolean',
            'plan_ids'       => 'array',
            'discount_value' => 'decimal:2',
            'min_amount'     => 'decimal:2',
        ];
    }

    public function validate(Plan $plan, float $amount): array
    {
        if (!$this->is_active) {
            return ['valid' => false, 'error' => 'Ce code promo n\'est plus actif.'];
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return ['valid' => false, 'error' => 'Ce code promo a expiré.'];
        }
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return ['valid' => false, 'error' => 'Ce code promo a atteint sa limite d\'utilisation.'];
        }
        if ($this->plan_ids !== null && !in_array($plan->id, $this->plan_ids)) {
            return ['valid' => false, 'error' => 'Ce code promo n\'est pas valable pour ce produit.'];
        }
        if ($this->min_amount !== null && $amount < $this->min_amount) {
            return ['valid' => false, 'error' => 'Montant minimum requis : ' . number_format($this->min_amount, 2) . '€.'];
        }

        $discount = $this->discount_type === 'percent'
            ? round($amount * $this->discount_value / 100, 2)
            : min($this->discount_value, $amount);

        $label = $this->discount_type === 'percent'
            ? '-' . rtrim(rtrim(number_format($this->discount_value, 2), '0'), '.') . '%'
            : '-' . number_format($discount, 2) . '€';

        return ['valid' => true, 'discount' => $discount, 'label' => $label];
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }
}
