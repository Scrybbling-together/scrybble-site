<?php

namespace Tests\Unit\Support;

use App\Support\Derive;
use App\Support\DerivesAttributes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\Derive\DeriveChild;
use Tests\Support\Derive\DeriveGrandparent;
use Tests\Support\Derive\DeriveParent;
use Tests\TestCase;

class DeriveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('derive_test_grandparents', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('derive_test_parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grandparent_id')->nullable();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('derive_test_children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable();
            $table->string('derived_label')->nullable();
            $table->timestamps();
        });
    }

    public function test_derives_attribute_from_parent(): void
    {
        $factory = new class extends Factory {
            use DerivesAttributes;
            protected $model = DeriveChild::class;

            public function definition(): array
            {
                return [
                    'derived_label' => Derive::from('parent.label'),
                ];
            }
        };

        $child = $factory
            ->for(DeriveParent::factory()->label('hello'), 'parent')
            ->create();

        $this->assertEquals('hello', $child->derived_label);
    }

    public function test_explicit_override_wins(): void
    {
        $factory = new class extends Factory {
            use DerivesAttributes;
            protected $model = DeriveChild::class;

            public function definition(): array
            {
                return [
                    'derived_label' => Derive::from('parent.label'),
                ];
            }
        };

        $child = $factory
            ->for(DeriveParent::factory()->label('hello'), 'parent')
            ->state(['derived_label' => 'overridden'])
            ->create();

        $this->assertEquals('overridden', $child->derived_label);
    }

    public function test_works_without_parent(): void
    {
        $factory = new class extends Factory {
            use DerivesAttributes;
            protected $model = DeriveChild::class;

            public function definition(): array
            {
                return [
                    'derived_label' => Derive::from('parent.label'),
                ];
            }
        };

        $child = $factory->create();

        $this->assertNull($child->derived_label);
    }

    public function test_coexists_with_factory_configure_callbacks(): void
    {
        $callbackRan = false;

        $factory = new class extends Factory {
            use DerivesAttributes;
            protected $model = DeriveChild::class;

            public function definition(): array
            {
                return [
                    'derived_label' => Derive::from('parent.label'),
                ];
            }
        };

        $child = $factory
            ->for(DeriveParent::factory()->label('hello'), 'parent')
            ->afterCreating(function () use (&$callbackRan) {
                $callbackRan = true;
            })
            ->create();

        $this->assertTrue($callbackRan);
        $this->assertEquals('hello', $child->derived_label);
    }

    public function test_derives_through_nested_relations(): void
    {
        $factory = new class extends Factory {
            use DerivesAttributes;
            protected $model = DeriveChild::class;

            public function definition(): array
            {
                return [
                    'derived_label' => Derive::from('parent.grandparent.label'),
                ];
            }
        };

        $grandparent = DeriveGrandparent::factory()->label('nested')->create();
        $parent = DeriveParent::factory()
            ->for($grandparent, 'grandparent')
            ->create();

        $child = $factory
            ->for($parent, 'parent')
            ->create();

        $this->assertEquals('nested', $child->derived_label);
    }

    public function test_derives_with_create_quietly(): void
    {
        $factory = new class extends Factory {
            use DerivesAttributes;
            protected $model = DeriveChild::class;

            public function definition(): array
            {
                return [
                    'derived_label' => Derive::from('parent.label'),
                ];
            }
        };

        $child = $factory
            ->for(DeriveParent::factory()->label('quiet'), 'parent')
            ->createQuietly();

        $this->assertEquals('quiet', $child->derived_label);
    }

    public function test_derives_across_multiple_models(): void
    {
        $factory = new class extends Factory {
            use DerivesAttributes;
            protected $model = DeriveChild::class;

            public function definition(): array
            {
                return [
                    'derived_label' => Derive::from('parent.label'),
                ];
            }
        };

        $children = $factory
            ->count(3)
            ->for(DeriveParent::factory()->label('batch'), 'parent')
            ->create();

        $this->assertCount(3, $children);
        $children->each(fn ($child) => $this->assertEquals('batch', $child->derived_label));
    }

    public function test_throws_when_relationship_does_not_exist(): void
    {
        $factory = new class extends Factory {
            use DerivesAttributes;
            protected $model = DeriveChild::class;

            public function definition(): array
            {
                return [
                    'derived_label' => Derive::from('nonExistent.label'),
                ];
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("has no 'nonExistent' relationship");

        $factory->create();
    }

    public function test_explicit_null_override_prevents_derivation(): void
    {
        $factory = new class extends Factory {
            use DerivesAttributes;
            protected $model = DeriveChild::class;

            public function definition(): array
            {
                return [
                    'derived_label' => Derive::from('parent.label'),
                ];
            }
        };

        $child = $factory
            ->for(DeriveParent::factory()->label('hello'), 'parent')
            ->state(['derived_label' => null])
            ->create();

        $this->assertNull($child->derived_label);
    }

    public function test_transforms_derived_value_with_closure(): void
    {
        $factory = new class extends Factory {
            use DerivesAttributes;
            protected $model = DeriveChild::class;

            public function definition(): array
            {
                return [
                    'derived_label' => Derive::from('parent.label')->transform(fn (string $value) => Str::slug($value)),
                ];
            }
        };

        $child = $factory
            ->for(DeriveParent::factory()->label('Hello World'), 'parent')
            ->create();

        $this->assertEquals('hello-world', $child->derived_label);
    }
}