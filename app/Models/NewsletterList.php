<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsletterList extends Model
{
    protected $fillable = ['name', 'description', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(NewsletterSubscriber::class, 'list_id');
    }

    public function activeSubscribers(): HasMany
    {
        return $this->hasMany(NewsletterSubscriber::class, 'list_id')->where('status', 'subscribed');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(NewsletterCampaign::class, 'list_id');
    }
}
