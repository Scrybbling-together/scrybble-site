<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

trait DerivesAttributes
{
    public function create($attributes = [], ?Model $parent = null)
    {
        $results = parent::create($attributes, $parent);

        // parent::create() recurses when $attributes is non-empty (converts to state),
        // so our override on the new instance already handled derivation
        if (!empty($attributes)) {
            return $results;
        }

        $derivations = collect($this->definition())
            ->filter(fn ($value) => $value instanceof Derive);

        if ($derivations->isEmpty()) {
            return $results;
        }

        // Determine which keys were explicitly set by state() calls
        $overriddenKeys = $this->states
            ->flatMap(fn ($state) => array_keys($state($this->definition(), null)))
            ->unique();

        $models = $results instanceof Model
            ? collect([$results])
            : $results;

        foreach ($models as $model) {
            $updates = [];

            foreach ($derivations as $column => $derive) {
                if ($overriddenKeys->contains($column)) {
                    continue;
                }

                $resolved = $derive->resolve($model);

                if ($resolved !== null) {
                    $updates[$column] = $resolved;
                }
            }

            if (!empty($updates)) {
                $model->forceFill($updates)->save();
                $model->refresh();
            }
        }

        return $results;
    }
}
