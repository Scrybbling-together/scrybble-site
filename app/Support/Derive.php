<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class Derive
{
    private function __construct(public readonly string $path) {}

    public static function from(string $path): static
    {
        return new static($path);
    }

    public function __invoke($definition): null
    {
        return null;
    }

    public function resolve(Model $model): mixed
    {
        return data_get($model, $this->path);
    }
}
