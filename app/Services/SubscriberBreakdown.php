<?php

namespace App\Services;

use App\Enums\SubscriptionPeriod;
use App\Models\GumroadSubscriber;
use App\Models\GumroadSale;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SubscriberBreakdown
{
    private function tierInnerQuery(): QueryBuilder
    {
        return GumroadSubscriber::query()
            ->from('gumroad_subscribers as gs')
            ->join('gumroad_sales as s', 's.subscription_id', '=', 'gs.subscriber_id')
            ->where('s.paid', true)
            ->groupBy('tier', 'gs.recurrence', 'is_trial')
            ->selectRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(s.variants, '$.Tier')), 'Unknown') as tier")
            ->selectRaw('gs.recurrence')
            ->selectRaw('(gs.free_trial_ends_at IS NOT NULL AND gs.free_trial_ends_at > NOW()) as is_trial')
            ->selectRaw('COUNT(DISTINCT gs.subscriber_id) as cnt')
            ->toBase();
    }

    private function pivotColumns(): array
    {
        $monthly = SubscriptionPeriod::Monthly->value;
        $yearly = SubscriptionPeriod::Yearly->value;
        $twoYearly = SubscriptionPeriod::EveryTwoYears->value;

        return [
            ['SUM(CASE WHEN recurrence = ? THEN cnt ELSE 0 END) as monthly', [$monthly]],
            ['SUM(CASE WHEN recurrence = ? THEN cnt ELSE 0 END) as yearly', [$yearly]],
            ['SUM(CASE WHEN recurrence = ? THEN cnt ELSE 0 END) as two_yearly', [$twoYearly]],
            ['SUM(cnt) as total', []],
        ];
    }

    public function byTierAndRecurrenceQuery(): QueryBuilder
    {
        $tiers = DB::query()->fromSub($this->tierInnerQuery(), 'grouped')
            ->where('is_trial', false)
            ->groupBy('tier')
            ->selectRaw('tier as id')
            ->selectRaw('tier')
            ->selectRaw('0 as sort_order');

        $trial = DB::query()->fromSub($this->tierInnerQuery(), 'trial')
            ->where('is_trial', true)
            ->selectRaw("'Trial' as id")
            ->selectRaw("'Trial' as tier")
            ->selectRaw('1 as sort_order');

        $totals = DB::query()->fromSub($this->tierInnerQuery(), 'totals')
            ->selectRaw("'Total' as id")
            ->selectRaw("'Total' as tier")
            ->selectRaw('2 as sort_order');

        foreach ($this->pivotColumns() as [$expr, $bindings]) {
            $tiers->selectRaw($expr, $bindings);
            $trial->selectRaw($expr, $bindings);
            $totals->selectRaw($expr, $bindings);
        }

        return $tiers->unionAll($trial)->unionAll($totals);
    }

    public function byTierAndRecurrence(): Collection
    {
        return $this->byTierAndRecurrenceQuery()->orderBy('sort_order')->get();
    }

    public function newSubscribersPerMonth(SubscriptionPeriod $period): Collection
    {
        $sub = GumroadSale::query()
            ->whereNotNull('subscription_id')
            ->where('paid', true)
            ->where('subscription_duration', $period)
            ->groupBy('subscription_id')
            ->select('subscription_id')
            ->selectRaw('MIN(created_at) as first_sale');

        return DB::query()->fromSub($sub, 'sub')
            ->selectRaw("DATE_FORMAT(first_sale, '%Y-%m') as month")
            ->selectRaw('COUNT(*) as cnt')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    public function cohortRetention(SubscriptionPeriod $period): Collection
    {
        $sub = GumroadSale::query()
            ->whereNotNull('subscription_id')
            ->where('paid', true)
            ->where('subscription_duration', $period)
            ->groupBy('subscription_id')
            ->select('subscription_id')
            ->selectRaw('MIN(created_at) as first_sale');

        return DB::query()->fromSub($sub, 'sub')
            ->leftJoin('gumroad_subscribers as gs', 'gs.subscriber_id', '=', 'sub.subscription_id')
            ->selectRaw("DATE_FORMAT(first_sale, '%Y-%m') as cohort_month")
            ->selectRaw('COUNT(*) as started')
            ->selectRaw('SUM(gs.subscriber_id IS NOT NULL) as still_active')
            ->groupBy('cohort_month')
            ->orderBy('cohort_month')
            ->get();
    }
}