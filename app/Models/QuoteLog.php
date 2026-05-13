<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteLog extends Model
{
    protected $fillable = ['quote_id', 'action', 'actor', 'note'];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function actionLabel(): string
    {
        return match($this->action) {
            'created'   => 'Devis créé',
            'sent'      => 'Envoyé au client',
            'viewed'    => 'Consulté par le client',
            'reminded'  => 'Relance envoyée',
            'accepted'  => 'Accepté par le client',
            'refused'   => 'Refusé par le client',
            'cancelled' => 'Annulé',
            'converted' => 'Converti en facture',
            default     => ucfirst($this->action),
        };
    }

    public function actionIcon(): string
    {
        return match($this->action) {
            'created'   => '✏️',
            'sent'      => '📤',
            'viewed'    => '👁',
            'reminded'  => '🔔',
            'accepted'  => '✅',
            'refused'   => '❌',
            'cancelled' => '🚫',
            'converted' => '🧾',
            default     => '•',
        };
    }
}
