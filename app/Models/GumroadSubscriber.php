<?php

namespace App\Models;

use App\Enums\SubscriptionPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GumroadSubscriber extends Model
{
    use HasFactory;
    protected $table = 'gumroad_subscribers';

    protected $primaryKey = 'subscriber_id';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = null;

    protected function casts(): array
    {
        return [
            'recurrence' => SubscriptionPeriod::class,
            'purchase_ids' => 'array',
            'created_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'ended_at' => 'datetime',
            'failed_at' => 'datetime',
            'free_trial_ends_at' => 'datetime',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(GumroadSale::class, 'subscription_id', 'subscriber_id');
    }

    public function scopeInTrial(Builder $query): Builder
    {
        return $query->whereNotNull('free_trial_ends_at')
            ->where('free_trial_ends_at', '>', now());
    }
}