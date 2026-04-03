<?php

namespace Tests\Feature\Analytics;

use App\Enums\SubscriberTier;
use App\Enums\SubscriptionPeriod;
use App\Filament\Widgets\ActiveSubscribersByTier;
use App\Models\GumroadSale;
use App\Models\GumroadSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActiveSubscribersByTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_has_expected_columns(): void
    {
        Livewire::test(ActiveSubscribersByTier::class)
            ->assertTableColumnExists('tier')
            ->assertTableColumnExists('trial')
            ->assertTableColumnExists('monthly')
            ->assertTableColumnExists('yearly')
            ->assertTableColumnExists('two_yearly')
            ->assertTableColumnExists('total_active')
            ->assertTableColumnExists('total');
    }

    public function test_table_always_shows_all_tier_rows_and_total(): void
    {
        Livewire::test(ActiveSubscribersByTier::class)
            ->assertSee(SubscriberTier::Student->label())
            ->assertSee(SubscriberTier::Professional->label())
            ->assertSee(SubscriberTier::Supporters->label())
            ->assertSee('Total');
    }

    public function test_trial_subscriber_counts_in_trial_column_for_their_tier(): void
    {
        GumroadSale::factory()
            ->for(GumroadSubscriber::factory()->inTrial(), 'subscriber')
            ->withTier(SubscriberTier::Professional)
            ->create();

        Livewire::test(ActiveSubscribersByTier::class)
            ->assertTableColumnStateSet('trial', 1, SubscriberTier::Professional->value)
            ->assertTableColumnStateSet('total', 1, SubscriberTier::Professional->value)
            ->assertTableColumnStateSet('total_active', 0, SubscriberTier::Professional->value);
    }

    public function test_active_subscriber_counts_in_recurrence_column_for_their_tier(): void
    {
        GumroadSale::factory()
            ->for(GumroadSubscriber::factory(), 'subscriber')
            ->withTier(SubscriberTier::Professional)
            ->create();

        Livewire::test(ActiveSubscribersByTier::class)
            ->assertTableColumnStateSet('monthly', 1, SubscriberTier::Professional->value)
            ->assertTableColumnStateSet('total_active', 1, SubscriberTier::Professional->value)
            ->assertTableColumnStateSet('total', 1, SubscriberTier::Professional->value)
            ->assertTableColumnStateSet('trial', 0, SubscriberTier::Professional->value);
    }

    public function test_total_row_sums_across_all_tiers(): void
    {
        GumroadSale::factory()
            ->for(GumroadSubscriber::factory(), 'subscriber')
            ->withTier(SubscriberTier::Professional)
            ->create();

        GumroadSale::factory()
            ->for(GumroadSubscriber::factory()->recurrence(SubscriptionPeriod::Yearly), 'subscriber')
            ->withTier(SubscriberTier::Student)
            ->create();

        Livewire::test(ActiveSubscribersByTier::class)
            ->assertTableColumnStateSet('monthly', 1, 'Total')
            ->assertTableColumnStateSet('yearly', 1, 'Total')
            ->assertTableColumnStateSet('total_active', 2, 'Total')
            ->assertTableColumnStateSet('total', 2, 'Total');
    }
}