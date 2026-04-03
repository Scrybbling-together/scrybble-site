<?php

namespace Database\Factories;

use App\Enums\SubscriberTier;
use App\Enums\SubscriptionPeriod;
use App\Models\GumroadSale;
use App\Models\GumroadSubscriber;
use App\Support\Derive;
use App\Support\DerivesAttributes;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GumroadSale> */
class GumroadSaleFactory extends Factory
{
    use DerivesAttributes;
    protected $model = GumroadSale::class;

    public function definition(): array
    {
        return [
            'sale_id' => $this->faker->unique()->uuid(),
            'subscription_id' => null,
            'created_at' => $this->faker->dateTimeBetween('-1 year'),
            'price' => $this->faker->numberBetween(500, 5000),
            'subscription_duration' => Derive::from('subscriber.recurrence'),
            'variants' => ['Tier' => SubscriberTier::Professional->value],
            'paid' => true,
        ];
    }

    public function forSubscriber(GumroadSubscriber $subscriber): static
    {
        return $this->state([
            'subscription_id' => $subscriber->subscriber_id,
        ]);
    }

    public function withTier(SubscriberTier $tier): static
    {
        return $this->state([
            'variants' => ['Tier' => $tier->value],
        ]);
    }

    public function recurrence(SubscriptionPeriod $period): static
    {
        return $this->state([
            'subscription_duration' => $period,
        ]);
    }

    public function unpaid(): static
    {
        return $this->state([
            'paid' => false,
        ]);
    }
}