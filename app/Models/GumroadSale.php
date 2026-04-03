<?php

namespace App\Models;

use App\Enums\SubscriberTier;
use App\Enums\SubscriptionPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GumroadSale extends Model
{
    use HasFactory;
    protected $table = 'gumroad_sales';

    protected $primaryKey = 'sale_id';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = null;

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'subscription_duration' => SubscriptionPeriod::class,
            'paid' => 'boolean',
            'cancelled' => 'boolean',
            'refunded' => 'boolean',
            'partially_refunded' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(GumroadSubscriber::class, 'subscription_id', 'subscriber_id');
    }

    public function tier(): ?SubscriberTier
    {
        return SubscriberTier::tryFrom($this->variants['Tier'] ?? '');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('paid', true);
    }

    public function scopeWithSubscription(Builder $query): Builder
    {
        return $query->whereNotNull('subscription_id');
    }

    public function scopeForPeriod(Builder $query, SubscriptionPeriod $period): Builder
    {
        return $query->where('subscription_duration', $period);
    }
}