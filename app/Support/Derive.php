<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Declarative attribute derivation for Eloquent factory definitions.
 *
 * Derive copies a value from a related model's attribute into the
 * model being created, with an optional transform step. Use it in
 * a factory definition alongside the {@see DerivesAttributes} trait.
 *
 * @example Basic usage — copy a parent's attribute directly:
 *
 *     // In a factory definition:
 *     'subscription_duration' => Derive::from('subscriber.recurrence'),
 *
 * @example Nested relations — traverse multiple levels:
 *
 *     'region' => Derive::from('store.company.headquarters.region'),
 *
 * @example Transform — apply a closure to the resolved value:
 *
 *     'slug' => Derive::from('parent.name')->transform(fn (string $name) => Str::slug($name)),
 *
 * @example Transform — compute a derived value:
 *
 *     'display_price' => Derive::from('product.price_cents')->transform(fn (int $cents) => $cents / 100),
 */
class Derive
{
    private ?Closure $transformer = null;

    /**
     * @param string $path Dot-notated path through relations to the source attribute.
     */
    private function __construct(
        public readonly string $path,
    ) {}

    /**
     * Create a new Derive instance for the given relation path.
     *
     * @param string $path Dot-notated path, e.g. 'parent.label' or 'order.customer.tier'.
     *
     * @example Derive::from('subscriber.recurrence')
     * @example Derive::from('order.customer.tier')
     */
    public static function from(string $path): static
    {
        return new static($path);
    }

    /**
     * Set a closure to transform the resolved value before assignment.
     *
     * @param Closure $transformer Receives the resolved value as its only argument.
     * @return $this
     *
     * @example Derive::from('parent.name')->transform(fn (string $name) => Str::slug($name))
     */
    public function transform(Closure $transformer): static
    {
        $this->transformer = $transformer;

        return $this;
    }

    /**
     * Called by Laravel's factory system when evaluating the definition array.
     * Returns null so the column is left empty until {@see resolve()} fills it.
     */
    public function __invoke($definition): null
    {
        return null;
    }

    /**
     * Resolve the derived value from the given model's relations.
     *
     * Traverses the dot-notated path using {@see data_get()}, then applies
     * the transform closure if one was provided.
     *
     * @param Model $model The model instance to resolve the value from.
     * @return mixed The resolved (and optionally transformed) value, or null if the path doesn't resolve.
     */
    public function resolve(Model $model): mixed
    {
        $value = data_get($model, $this->path);

        if ($value !== null && $this->transformer !== null) {
            return ($this->transformer)($value);
        }

        return $value;
    }
}