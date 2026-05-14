<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = ['user_id', 'type', 'title', 'body', 'url', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }

    public function icon(): string
    {
        return match($this->type) {
            'quote_sent'           => '📄',
            'quote_message'        => '💬',
            'invoice_created'      => '🧾',
            'payment_confirmed'    => '✅',
            'payment_received'     => '💳',
            'invoice_overdue'      => '⚠️',
            'vm_renewal_soon'      => '🖥️',
            'hosting_renewal_soon' => '🌐',
            'service_cancelled'    => '❌',
            'project_update'       => '🔄',
            'project_message'      => '💬',
            'ticket_created'       => '🎫',
            default                => '🔔',
        };
    }
}
