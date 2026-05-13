<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'quote_id', 'type', 'deposit_invoice_id',
        'number', 'stripe_invoice_id', 'stripe_payment_intent_id',
        'status', 'subtotal', 'tax', 'total', 'currency',
        'items', 'metadata', 'paid_at', 'due_at',
    ];

    protected function casts(): array
    {
        return [
            'items'    => 'array',
            'metadata' => 'array',
            'paid_at'  => 'datetime',
            'due_at'   => 'datetime',
            'subtotal' => 'decimal:2',
            'tax'      => 'decimal:2',
            'total'    => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function depositInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'deposit_invoice_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function typeLabel(): string
    {
        return match($this->type ?? 'manual') {
            'subscription' => 'Abonnement',
            'manual'       => 'Manuelle',
            'deposit'      => 'Acompte',
            'balance'      => 'Solde',
            default        => 'Manuelle',
        };
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('INV-%s-%04d', $year, $count);
    }
}
