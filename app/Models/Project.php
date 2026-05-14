<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'user_id', 'title', 'description', 'status', 'steps', 'due_date',
    ];

    protected function casts(): array
    {
        return [
            'steps'    => 'array',
            'due_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ProjectMessage::class)->orderBy('created_at');
    }

    public function progressPercent(): int
    {
        $steps = $this->steps ?? [];
        if (empty($steps)) {
            return match($this->status) {
                'completed'  => 100,
                'cancelled'  => 0,
                'in_progress'=> 50,
                'review'     => 75,
                default      => 0,
            };
        }
        $total     = count($steps);
        $completed = collect($steps)->where('status', 'completed')->count();
        return (int) round($completed / $total * 100);
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'pending'     => 'En attente',
            'in_progress' => 'En cours',
            'review'      => 'En révision',
            'completed'   => 'Terminé',
            'cancelled'   => 'Annulé',
            default       => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'pending'     => 'bg-gray-100 text-gray-600',
            'in_progress' => 'bg-blue-100 text-blue-700',
            'review'      => 'bg-amber-100 text-amber-700',
            'completed'   => 'bg-green-100 text-green-700',
            'cancelled'   => 'bg-red-100 text-red-600',
            default       => 'bg-gray-100 text-gray-600',
        };
    }
}
