<?php

namespace Tests\Unit\Support;

use App\Enums\SubscriptionPeriod;
use App\Models\GumroadSale;
use App\Models\GumroadSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeriveTest extends TestCase
{
    use RefreshDatabase;

    public function test_derives_attribute_from_parent(): void
    {
        $sale = GumroadSale::factory()
            ->for(GumroadSubscriber::factory()->recurrence(SubscriptionPeriod::Yearly), 'subscriber')
            ->create();

        $this->assertEquals(SubscriptionPeriod::Yearly, $sale->subscription_duration);
    }

    public function test_explicit_override_wins(): void
    {
        $sale = GumroadSale::factory()
            ->for(GumroadSubscriber::factory()->recurrence(SubscriptionPeriod::Yearly), 'subscriber')
            ->recurrence(SubscriptionPeriod::Monthly)
            ->create();

        $this->assertEquals(SubscriptionPeriod::Monthly, $sale->subscription_duration);
    }

    public function test_works_without_parent(): void
    {
        $sale = GumroadSale::factory()->create();

        $this->assertNull($sale->subscription_duration);
    }

    public function test_coexists_with_factory_configure_callbacks(): void
    {
        $callbackRan = false;

        $sale = GumroadSale::factory()
            ->for(GumroadSubscriber::factory()->recurrence(SubscriptionPeriod::Yearly), 'subscriber')
            ->afterCreating(function () use (&$callbackRan) {
                $callbackRan = true;
            })
            ->create();

        $this->assertTrue($callbackRan);
        $this->assertEquals(SubscriptionPeriod::Yearly, $sale->subscription_duration);
    }
}
