<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNote extends Model
{
    protected $fillable = [
        'number', 'invoice_id', 'user_id', 'reason', 'amount', 'currency', 'status', 'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'    => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('AV-%s-%04d', $year, $count);
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'draft'   => 'Brouillon',
            'issued'  => 'Émis',
            'applied' => 'Appliqué',
            default   => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'draft'   => 'bg-gray-100 text-gray-600',
            'issued'  => 'bg-blue-100 text-blue-700',
            'applied' => 'bg-green-100 text-green-700',
            default   => 'bg-gray-100 text-gray-600',
        };
    }
}
