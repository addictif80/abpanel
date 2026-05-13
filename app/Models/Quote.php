<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Quote extends Model
{
    protected $fillable = [
        'number', 'user_id', 'status', 'subject', 'notes', 'internal_notes',
        'is_template', 'template_name',
        'subtotal', 'discount_amount', 'discount_type', 'tax_amount', 'total',
        'deposit_percent', 'currency', 'access_token',
        'sent_at', 'opened_at', 'last_viewed_at', 'accepted_at', 'refused_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'        => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount'      => 'decimal:2',
            'total'           => 'decimal:2',
            'deposit_percent' => 'decimal:2',
            'is_template'     => 'boolean',
            'sent_at'         => 'datetime',
            'opened_at'       => 'datetime',
            'last_viewed_at'  => 'datetime',
            'accepted_at'     => 'datetime',
            'refused_at'      => 'datetime',
            'expires_at'      => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $quote) {
            if (empty($quote->access_token)) {
                $quote->access_token = Str::random(64);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function depositInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->where('type', 'deposit');
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('DEV-%s-%04d', $year, $count);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'sent']);
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['sent', 'viewed']);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast() && ! in_array($this->status, ['accepted', 'refused', 'invoiced', 'cancelled']);
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'draft'    => 'Brouillon',
            'sent'     => 'Envoyé',
            'viewed'   => 'Consulté',
            'accepted' => 'Accepté',
            'refused'  => 'Refusé',
            'expired'  => 'Expiré',
            'invoiced' => 'Facturé',
            'cancelled'=> 'Annulé',
            default    => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'draft'    => 'bg-gray-100 text-gray-600',
            'sent'     => 'bg-blue-100 text-blue-700',
            'viewed'   => 'bg-indigo-100 text-indigo-700',
            'accepted' => 'bg-green-100 text-green-700',
            'refused'  => 'bg-red-100 text-red-700',
            'expired'  => 'bg-orange-100 text-orange-700',
            'invoiced' => 'bg-purple-100 text-purple-700',
            'cancelled'=> 'bg-gray-100 text-gray-500',
            default    => 'bg-gray-100 text-gray-600',
        };
    }

    public function recomputeTotals(): void
    {
        $subtotal = $this->items->sum(fn($item) => $item->computeTotal());

        $globalDiscount = $this->discount_type === 'percent'
            ? $subtotal * ((float) $this->discount_amount / 100)
            : (float) $this->discount_amount;

        $taxableBase = max(0, $subtotal - $globalDiscount);
        $taxAmount = $this->items->sum(fn($item) => $taxableBase > 0
            ? round($item->computeTotal() * ((float) $item->tax_rate / 100), 2)
            : 0);

        $this->subtotal   = round($subtotal, 2);
        $this->tax_amount = round($taxAmount, 2);
        $this->total      = round($taxableBase + $taxAmount, 2);
    }

    public function depositAmount(): float
    {
        if ((float) $this->deposit_percent <= 0) {
            return 0;
        }
        return round($this->total * ((float) $this->deposit_percent / 100), 2);
    }

    public function balanceAmount(): float
    {
        return round($this->total - $this->depositAmount(), 2);
    }
}
