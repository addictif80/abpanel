<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name', 'reference', 'description', 'unit_price', 'unit',
        'tax_rate', 'hidden_from_pro', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'unit_price'      => 'decimal:2',
            'tax_rate'        => 'decimal:2',
            'hidden_from_pro' => 'boolean',
            'is_active'       => 'boolean',
        ];
    }

    public function quoteItems(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function isHiddenForUser(User $user): bool
    {
        return $this->hidden_from_pro && ! empty($user->siret);
    }

    public static function unitLabel(string $unit): string
    {
        return match($unit) {
            'hour'    => 'heure',
            'day'     => 'jour',
            'month'   => 'mois',
            'year'    => 'an',
            'page'    => 'page',
            'forfait' => 'forfait',
            default   => $unit,
        };
    }
}
