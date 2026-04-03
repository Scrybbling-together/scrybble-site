<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

trait DerivesAttributes
{
    public function create($attributes = [], ?Model $parent = null)
    {
        $results = parent::create($attributes, $parent);

        $derivations = collect($this->definition())
            ->filter(fn ($value) => $value instanceof Derive);

        if ($derivations->isEmpty()) {
            return $results;
        }

        $models = $results instanceof Model
            ? collect([$results])
            : $results;

        foreach ($models as $model) {
            $updates = [];

            foreach ($derivations as $column => $derive) {
                if ($model->getAttribute($column) !== null) {
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
