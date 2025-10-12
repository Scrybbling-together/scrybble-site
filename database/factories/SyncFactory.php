<?php

namespace Database\Factories;

use App\Models\Sync;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sync>
 */
class SyncFactory extends Factory
{
    protected $model = Sync::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'filename' => $this->faker->randomElement([
                '/documents/notebook.pdf',
                '/sketches/drawing.pdf',
                '/notes/meeting_notes.pdf',
                '/journal/daily_log.pdf'
            ]),
            'completed' => false,
            'sync_id' => null,
        ];
    }

    /**
     * Indicate that the sync is completed.
     *
     * @return static
     */
    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'completed' => true,
                'sync_id' => $this->faker->uuid(),
            ];
        });
    }

    /**
     * Indicate that the sync is for a missing file (exists in DB but not filesystem).
     *
     * @return static
     */
    public function missingFile()
    {
        return $this->state(function (array $attributes) {
            return [
                'completed' => true,
                'sync_id' => 'missing-file-' . $this->faker->uuid(),
                'filename' => '/deleted/' . $this->faker->word() . '.pdf',
            ];
        });
    }
}
