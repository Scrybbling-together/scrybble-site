<?php

namespace Tests\Support\Derive;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DeriveGrandparent> */
class DeriveGrandparentFactory extends Factory
{
    protected $model = DeriveGrandparent::class;

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