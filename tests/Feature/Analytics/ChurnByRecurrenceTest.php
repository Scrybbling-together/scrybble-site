<?php

namespace Tests\Feature\Analytics;

use App\Enums\SubscriptionPeriod;
use App\Filament\Widgets\ChurnByRecurrence;
use App\Models\GumroadSale;
use App\Models\GumroadSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChurnByRecurrenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_has_expected_columns(): void
    {
        Livewire::test(ChurnByRecurrence::class)
            ->assertTableColumnExists('recurrence')
            ->assertTableColumnExists('n')
            ->assertTableColumnExists('avg_lifetime_charges');
    }

    public function test_churned_monthly_subscriber_appears_with_correct_lifetime(): void
    {
        $subId = 'churned-monthly-' . fake()->uuid();
        GumroadSale::factory()
            ->count(3)
            ->recurrence(SubscriptionPeriod::Monthly)
            ->create(['subscription_id' => $subId]);

        Livewire::test(ChurnByRecurrence::class)
            ->assertTableColumnStateSet('recurrence', SubscriptionPeriod::Monthly->value, SubscriptionPeriod::Monthly->value)
            ->assertTableColumnStateSet('n', 1, SubscriptionPeriod::Monthly->value)
            ->assertTableColumnStateSet('avg_lifetime_charges', '3.0', SubscriptionPeriod::Monthly->value);
    }

    public function test_active_subscriber_is_excluded(): void
    {
        GumroadSale::factory()
            ->for(GumroadSubscriber::factory(), 'subscriber')
            ->create();

        Livewire::test(ChurnByRecurrence::class)
            ->assertTableColumnStateNotSet('n', 1, SubscriptionPeriod::Monthly->value);
    }

    public function test_groups_by_recurrence(): void
    {
        $monthlyId = 'churned-m-' . fake()->uuid();
        GumroadSale::factory()
            ->count(2)
            ->recurrence(SubscriptionPeriod::Monthly)
            ->create(['subscription_id' => $monthlyId]);

        $yearlyId = 'churned-y-' . fake()->uuid();
        GumroadSale::factory()
            ->count(4)
            ->recurrence(SubscriptionPeriod::Yearly)
            ->create(['subscription_id' => $yearlyId]);

        Livewire::test(ChurnByRecurrence::class)
            ->assertTableColumnStateSet('n', 1, SubscriptionPeriod::Monthly->value)
            ->assertTableColumnStateSet('avg_lifetime_charges', '2.0', SubscriptionPeriod::Monthly->value)
            ->assertTableColumnStateSet('n', 1, SubscriptionPeriod::Yearly->value)
            ->assertTableColumnStateSet('avg_lifetime_charges', '4.0', SubscriptionPeriod::Yearly->value);
    }
}
