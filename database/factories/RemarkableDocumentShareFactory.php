<?php

namespace Database\Factories;

use App\Models\RemarkableDocumentShare;
use App\Models\Sync;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RemarkableDocumentShare>
 */
class RemarkableDocumentShareFactory extends Factory
{
    protected $model = RemarkableDocumentShare::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'sync_id' => Sync::factory(),
            'feedback' => $this->faker->optional()->sentence(),
            'developer_access_consent_granted' => false,
            'open_access_consent_granted' => false,
        ];
    }

    public function openAccess(): static
    {
        return $this->state(fn (array $attributes) => [
            'open_access_consent_granted' => true,
        ]);
    }

    public function developerAccess(): static
    {
        return $this->state(fn (array $attributes) => [
            'developer_access_consent_granted' => true,
        ]);
    }
}
