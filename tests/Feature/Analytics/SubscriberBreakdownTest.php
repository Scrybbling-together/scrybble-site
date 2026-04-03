<?php

namespace Tests\Feature\Analytics;

use App\Enums\SubscriberTier;
use App\Enums\SubscriptionPeriod;
use App\Models\GumroadSale;
use App\Models\GumroadSubscriber;
use App\Services\SubscriberBreakdown;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriberBreakdownTest extends TestCase
{
    use RefreshDatabase;

    private SubscriberBreakdown $breakdown;

    protected function setUp(): void
    {
        parent::setUp();
        $this->breakdown = app(SubscriberBreakdown::class);
    }

    public function test_by_tier_and_recurrence_returns_tier_trial_and_total_rows(): void
    {
        // Monthly professional subscriber
        $monthlyPro = GumroadSubscriber::factory()->create();
        GumroadSale::factory()
            ->forSubscriber($monthlyPro)
            ->withTier(SubscriberTier::Professional)
            ->create();

        // Yearly student subscriber
        $yearlyStudent = GumroadSubscriber::factory()->yearly()->create();
        GumroadSale::factory()
            ->forSubscriber($yearlyStudent)
            ->withTier(SubscriberTier::Student)
            ->yearly()
            ->create();

        // Trial subscriber (monthly, professional)
        $trial = GumroadSubscriber::factory()->inTrial()->create();
        GumroadSale::factory()
            ->forSubscriber($trial)
            ->withTier(SubscriberTier::Professional)
            ->create();

        $result = $this->breakdown->byTierAndRecurrence();

        // Should have: Professional row, Student row, Trial row, Total row
        $this->assertGreaterThanOrEqual(4, $result->count());

        $total = $result->firstWhere('tier', 'Total');
        $this->assertNotNull($total);
        $this->assertEquals(3, $total->total);

        $trialRow = $result->firstWhere('tier', 'Trial');
        $this->assertNotNull($trialRow);
        $this->assertEquals(1, $trialRow->total);
    }

    public function test_new_subscribers_per_month_counts_first_sales(): void
    {
        // Subscriber A: first sale in Jan 2025
        $subA = GumroadSubscriber::factory()->create();
        GumroadSale::factory()->forSubscriber($subA)->create([
            'created_at' => '2025-01-15',
        ]);
        GumroadSale::factory()->forSubscriber($subA)->create([
            'created_at' => '2025-02-15',
        ]);

        // Subscriber B: first sale in Jan 2025
        $subB = GumroadSubscriber::factory()->create();
        GumroadSale::factory()->forSubscriber($subB)->create([
            'created_at' => '2025-01-20',
        ]);

        // Subscriber C: first sale in Feb 2025
        $subC = GumroadSubscriber::factory()->create();
        GumroadSale::factory()->forSubscriber($subC)->create([
            'created_at' => '2025-02-10',
        ]);

        $result = $this->breakdown->newSubscribersPerMonth(SubscriptionPeriod::Monthly);

        $jan = $result->firstWhere('month', '2025-01');
        $feb = $result->firstWhere('month', '2025-02');

        $this->assertEquals(2, $jan->cnt); // A and B started in Jan
        $this->assertEquals(1, $feb->cnt); // Only C started in Feb
    }

    public function test_new_subscribers_per_month_filters_by_period(): void
    {
        // Monthly subscriber
        $monthly = GumroadSubscriber::factory()->create();
        GumroadSale::factory()->forSubscriber($monthly)->create([
            'created_at' => '2025-03-01',
        ]);

        // Yearly subscriber (should not appear in monthly query)
        $yearly = GumroadSubscriber::factory()->yearly()->create();
        GumroadSale::factory()->forSubscriber($yearly)->yearly()->create([
            'created_at' => '2025-03-01',
        ]);

        $result = $this->breakdown->newSubscribersPerMonth(SubscriptionPeriod::Monthly);

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result->first()->cnt);
    }

    public function test_cohort_retention_tracks_active_vs_started(): void
    {
        // Jan cohort: 2 started, 1 still active
        $activeJan = GumroadSubscriber::factory()->create();
        GumroadSale::factory()->forSubscriber($activeJan)->create([
            'created_at' => '2025-01-10',
        ]);

        $churnedJanSubId = 'churned-jan-' . fake()->uuid();
        GumroadSale::factory()->create([
            'subscription_id' => $churnedJanSubId,
            'created_at' => '2025-01-20',
        ]);

        // Feb cohort: 1 started, 1 still active
        $activeFeb = GumroadSubscriber::factory()->create();
        GumroadSale::factory()->forSubscriber($activeFeb)->create([
            'created_at' => '2025-02-05',
        ]);

        $result = $this->breakdown->cohortRetention(SubscriptionPeriod::Monthly);

        $jan = $result->firstWhere('cohort_month', '2025-01');
        $feb = $result->firstWhere('cohort_month', '2025-02');

        $this->assertEquals(2, $jan->started);
        $this->assertEquals(1, $jan->still_active);
        $this->assertEquals(1, $feb->started);
        $this->assertEquals(1, $feb->still_active);
    }
}