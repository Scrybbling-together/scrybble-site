<?php

namespace Tests\Support\Derive;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DeriveParent> */
class DeriveParentFactory extends Factory
{
    protected $model = DeriveParent::class;

    public function definition(): array
    {
        return [
            'label' => 'default',
        ];
    }

    public function label(string $label): static
    {
        return $this->state(['label' => $label]);
    }
}