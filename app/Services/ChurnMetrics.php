<?php

namespace App\Services;

use App\Models\GumroadSale;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChurnMetrics
{
    public function trialConversion(): object
    {
        $result = DB::selectOne("
            SELECT
                SUM(sale_count >= 2) as converted,
                SUM(sale_count = 1 AND NOT still_in_trial) as not_converted,
                SUM(sale_count = 1 AND still_in_trial) as in_trial
            FROM (
                SELECT
                    s.subscription_id,
                    COUNT(*) as sale_count,
                    (gs.free_trial_ends_at IS NOT NULL AND gs.free_trial_ends_at > NOW()) as still_in_trial
                FROM gumroad_sales s
                LEFT JOIN gumroad_subscribers gs ON gs.subscriber_id = s.subscription_id
                WHERE s.subscription_id IS NOT NULL AND s.paid = 1
                GROUP BY s.subscription_id, still_in_trial
            ) sub
        ");

        $converted = (int) ($result->converted ?? 0);
        $notConverted = (int) ($result->not_converted ?? 0);
        $inTrial = (int) ($result->in_trial ?? 0);
        $decided = $converted + $notConverted;
        $rate = $decided > 0 ? round($converted / $decided * 100, 1) : 0;

        return (object) compact('converted', 'notConverted', 'inTrial', 'rate');
    }

    public function paidChurn(): object
    {
        $result = DB::selectOne("
            SELECT
                COUNT(DISTINCT s.subscription_id) as total,
                SUM(gs.subscriber_id IS NULL) as churned,
                SUM(gs.subscriber_id IS NOT NULL) as active
            FROM (
                SELECT subscription_id
                FROM gumroad_sales
                WHERE subscription_id IS NOT NULL AND paid = 1
                GROUP BY subscription_id
            ) s
            LEFT JOIN gumroad_subscribers gs ON gs.subscriber_id = s.subscription_id
        ");

        $total = (int) ($result->total ?? 0);
        $churned = (int) ($result->churned ?? 0);
        $active = (int) ($result->active ?? 0);
        $rate = $total > 0 ? round($churned / $total * 100, 1) : 0;

        return (object) compact('total', 'churned', 'active', 'rate');
    }

    public function avgLifetime(): object
    {
        $result = DB::selectOne("
            SELECT
                ROUND(AVG(charge_count), 1) as avg_charges,
                MIN(charge_count) as min_charges,
                MAX(charge_count) as max_charges,
                COUNT(*) as n
            FROM (
                SELECT s.subscription_id, COUNT(*) as charge_count
                FROM gumroad_sales s
                LEFT JOIN gumroad_subscribers gs ON gs.subscriber_id = s.subscription_id
                WHERE s.subscription_id IS NOT NULL
                  AND s.paid = 1
                  AND gs.subscriber_id IS NULL
                GROUP BY s.subscription_id
            ) churned
        ");

        return (object) [
            'avg_charges' => $result->avg_charges ?? 0,
            'min_charges' => (int) ($result->min_charges ?? 0),
            'max_charges' => (int) ($result->max_charges ?? 0),
            'n' => (int) ($result->n ?? 0),
        ];
    }

    public function churnByRecurrenceQuery(): Builder
    {
        $sub = GumroadSale::query()
            ->from('gumroad_sales as s')
            ->leftJoin('gumroad_subscribers as gs', 'gs.subscriber_id', '=', 's.subscription_id')
            ->whereNotNull('s.subscription_id')
            ->where('s.paid', true)
            ->whereNull('gs.subscriber_id')
            ->groupBy('s.subscription_id')
            ->selectRaw('s.subscription_id')
            ->selectRaw('COUNT(*) as charge_count')
            ->selectRaw('MIN(s.subscription_duration) as recurrence')
            ->toBase();

        return DB::query()->fromSub($sub, 'churned')
            ->groupBy('recurrence')
            ->selectRaw('recurrence as id')
            ->selectRaw('recurrence')
            ->selectRaw('COUNT(*) as n')
            ->selectRaw('ROUND(AVG(charge_count), 1) as avg_lifetime_charges');
    }

    public function churnByRecurrence(): Collection
    {
        return $this->churnByRecurrenceQuery()->orderByDesc('n')->get();
    }
}