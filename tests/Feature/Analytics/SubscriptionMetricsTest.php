<?php

namespace Tests\Feature\Analytics;

use App\Filament\Widgets\SubscriptionMetrics;
use App\Models\GumroadSale;
use App\Models\GumroadSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubscriptionMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_conversion_rate_shows_correct_percentage(): void
    {
        // Converted: subscriber with 2 paid sales (renewed past trial)
        GumroadSale::factory()->for(GumroadSubscriber::factory(), 'subscriber')->createMany(2);
        // Not converted: 1 paid sale, no subscriber row (churned)
        GumroadSale::factory()->create(['subscription_id' => 'churned-' . fake()->uuid()]);

        Livewire::test(SubscriptionMetrics::class)
            ->assertSee('50%')
            ->assertSee('1 converted, 1 churned, 0 in trial');
    }

    public function test_paid_churn_rate_shows_correct_percentage(): void
    {
        // Active: subscriber row exists with paid sales
        GumroadSale::factory()->for(GumroadSubscriber::factory(), 'subscriber')->createMany(2);
        // Churned: paid sales but no subscriber row
        GumroadSale::factory()->create(['subscription_id' => 'churned-' . fake()->uuid()]);

        Livewire::test(SubscriptionMetrics::class)
            ->assertSee('1 churned, 1 active out of 2 paid');
    }

    public function test_avg_subscriber_lifetime_shows_correct_charges(): void
    {
        // Churned subscriber A: 2 charges
        GumroadSale::factory()->count(2)->create(['subscription_id' => 'churned-a-' . fake()->uuid()]);
        // Churned subscriber B: 4 charges
        GumroadSale::factory()->count(4)->create(['subscription_id' => 'churned-b-' . fake()->uuid()]);

        Livewire::test(SubscriptionMetrics::class)
            ->assertSee('3.0 charges')
            ->assertSee('Min 2, Max 4 across 2 churned subscribers');
    }
}
