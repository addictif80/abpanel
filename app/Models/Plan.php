<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'type', 'vm_type', 'description', 'price', 'currency',
        'billing_period', 'stripe_price_id', 'features',
        'cores', 'memory_mb', 'disk_gb', 'cyberpanel_package', 'is_active', 'sort_order',
        'limit_per_client', 'limit_per_vm', 'limit_per_hosting', 'requires_vm', 'requires_hosting',
    ];

    protected function casts(): array
    {
        return [
            'features'         => 'array',
            'is_active'        => 'boolean',
            'price'            => 'decimal:2',
            'requires_vm'      => 'boolean',
            'requires_hosting' => 'boolean',
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

    /**
     * Vérifie si un client peut commander ce plan.
     * Retourne null si autorisé, ou un message d'erreur lisible sinon.
     */
    public function checkClientLimit(User $user): ?string
    {
        $vmCount      = $user->virtualMachines()->count();
        $hostingCount = $user->hostingAccounts()->count();

        if ($this->requires_vm && $vmCount === 0) {
            return 'Vous devez posséder au moins un VPS actif pour commander ce produit.';
        }

        if ($this->requires_hosting && $hostingCount === 0) {
            return 'Vous devez posséder au moins un hébergement actif pour commander ce produit.';
        }

        $maxAllowed = $this->computeMaxAllowed($vmCount, $hostingCount);

        if ($maxAllowed !== null) {
            $currentCount = $this->currentUsageCount($user);
            if ($currentCount >= $maxAllowed) {
                return "Vous avez atteint la limite pour ce produit ({$currentCount}/{$maxAllowed}).";
            }
        }

        return null;
    }

    private function currentUsageCount(User $user): int
    {
        if ($this->type === 'vm') {
            return $user->virtualMachines()->where('plan_id', $this->id)->count();
        }

        return Invoice::where('user_id', $user->id)
            ->where('plan_id', $this->id)
            ->where('status', 'paid')
            ->count();
    }

    private function computeMaxAllowed(int $vmCount, int $hostingCount): ?int
    {
        $max = $this->limit_per_client;

        if ($this->limit_per_vm !== null) {
            $vmBased = $vmCount * $this->limit_per_vm;
            $max     = $max === null ? $vmBased : min($max, $vmBased);
        }

        if ($this->limit_per_hosting !== null) {
            $hostingBased = $hostingCount * $this->limit_per_hosting;
            $max          = $max === null ? $hostingBased : min($max, $hostingBased);
        }

        return $max;
    }
}
