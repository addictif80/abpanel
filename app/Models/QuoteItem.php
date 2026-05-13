<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    protected $fillable = [
        'quote_id', 'product_id', 'description', 'details',
        'quantity', 'unit', 'unit_price',
        'discount_amount', 'discount_type', 'tax_rate',
        'total', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity'        => 'decimal:2',
            'unit_price'      => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate'        => 'decimal:2',
            'total'           => 'decimal:2',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function computeTotal(): float
    {
        $base = (float) $this->quantity * (float) $this->unit_price;

        $discount = $this->discount_type === 'percent'
            ? $base * ((float) $this->discount_amount / 100)
            : (float) $this->discount_amount;

        return round(max(0, $base - $discount), 2);
    }
}
