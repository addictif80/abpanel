<?php

namespace App\Models;

use App\Models\Concerns\HasRetryableNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes, HasRetryableNumber;

    protected $fillable = [
        'user_id', 'plan_id', 'quote_id', 'type', 'deposit_invoice_id',
        'number', 'stripe_invoice_id', 'stripe_payment_intent_id',
        'status', 'subtotal', 'tax', 'total', 'currency',
        'items', 'metadata', 'billing_snapshot', 'paid_at', 'due_at',
        'is_recurring', 'recurrence_period', 'next_billing_at',
        'promo_code_id', 'discount',
    ];

    protected static function booted(): void
    {
        // Freeze the buyer's details as they were at issuance. A legal
        // invoice must keep them unchanged for its statutory retention
        // period even if the client's live profile is later anonymized.
        static::creating(function (self $invoice) {
            if ($invoice->billing_snapshot || !$invoice->user_id) {
                return;
            }

            $user = $invoice->relationLoaded('user') ? $invoice->user : User::find($invoice->user_id);
            if ($user) {
                $invoice->billing_snapshot = static::snapshotFor($user);
            }
        });
    }

    public static function snapshotFor(User $user): array
    {
        return [
            'name'       => $user->full_name,
            'email'      => $user->email,
            'company'    => $user->company,
            'address'    => $user->address,
            'city'       => $user->city,
            'zip'        => $user->zip,
            'country'    => $user->country,
            'siret'      => $user->siret,
            'vat_number' => $user->vat_number,
        ];
    }

    /** Buyer details to display/print for this invoice: the frozen snapshot
     * when available, falling back to the live user for invoices created
     * before this column existed. */
    public function billingInfo(): array
    {
        return $this->billing_snapshot ?: static::snapshotFor($this->user ?? new User());
    }

    protected function casts(): array
    {
        return [
            'items'           => 'array',
            'metadata'        => 'array',
            'billing_snapshot' => 'array',
            'paid_at'         => 'datetime',
            'due_at'          => 'datetime',
            'next_billing_at' => 'date',
            'subtotal'        => 'decimal:2',
            'tax'             => 'decimal:2',
            'total'           => 'decimal:2',
            'discount'        => 'decimal:2',
            'is_recurring'    => 'boolean',
        ];
    }

    public function recurrenceLabel(): string
    {
        return match($this->recurrence_period) {
            'monthly'   => 'Mensuelle',
            'quarterly' => 'Trimestrielle',
            'yearly'    => 'Annuelle',
            default     => '—',
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
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
        $last = static::withTrashed()->where('number', 'like', "INV-{$year}-%")->orderByDesc('id')->value('number');
        $next = $last ? ((int) substr($last, strrpos($last, '-') + 1)) + 1 : 1;
        return sprintf('INV-%s-%04d', $year, $next);
    }
}
