<?php

namespace Database\Factories;

use App\Enums\SubscriptionPeriod;
use App\Models\GumroadSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GumroadSubscriber> */
class GumroadSubscriberFactory extends Factory
{
    protected $model = GumroadSubscriber::class;

    public function definition(): array
    {
        return [
            'subscriber_id' => $this->faker->unique()->uuid(),
            'customer_hash' => $this->faker->sha1(),
            'product_id' => 'prod_' . $this->faker->bothify('??######'),
            'recurrence' => SubscriptionPeriod::Monthly,
            'status' => 'alive',
            'created_at' => $this->faker->dateTimeBetween('-1 year'),
        ];
    }

    public function inTrial(): static
    {
        return $this->state([
            'free_trial_ends_at' => now()->addDays(7),
        ]);
    }

    public function yearly(): static
    {
        return $this->state([
            'recurrence' => SubscriptionPeriod::Yearly,
        ]);
    }

    public function everyTwoYears(): static
    {
        return $this->state([
            'recurrence' => SubscriptionPeriod::EveryTwoYears,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => 'cancelled',
            'cancelled_at' => now()->subDays(30),
        ]);
    }
}