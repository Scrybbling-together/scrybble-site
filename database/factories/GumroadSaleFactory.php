<?php

namespace Database\Factories;

use App\Enums\SubscriberTier;
use App\Enums\SubscriptionPeriod;
use App\Models\GumroadSale;
use App\Models\GumroadSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GumroadSale> */
class GumroadSaleFactory extends Factory
{
    protected $model = GumroadSale::class;

    public function definition(): array
    {
        return [
            'sale_id' => $this->faker->unique()->uuid(),
            'subscription_id' => null,
            'created_at' => $this->faker->dateTimeBetween('-1 year'),
            'price' => $this->faker->numberBetween(500, 5000),
            'subscription_duration' => SubscriptionPeriod::Monthly,
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

    public function yearly(): static
    {
        return $this->state([
            'subscription_duration' => SubscriptionPeriod::Yearly,
        ]);
    }

    public function unpaid(): static
    {
        return $this->state([
            'paid' => false,
        ]);
    }
}