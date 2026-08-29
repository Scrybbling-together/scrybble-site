<?php

namespace Database\Factories;

use App\Models\Sync;
use App\Models\SyncLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyncLog>
 */
class SyncLogFactory extends Factory
{
    protected $model = SyncLog::class;

    public function definition(): array
    {
        return [
            'sync_id' => Sync::factory(),
            'message' => $this->faker->sentence(),
            'severity' => 'info',
            'context' => null,
        ];
    }

    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'error',
        ]);
    }

    public function withContext(array $context): static
    {
        return $this->state(fn (array $attributes) => [
            'context' => $context,
        ]);
    }
}
