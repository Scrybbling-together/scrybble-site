<?php

namespace App\Filament\Widgets;

use App\Services\ChurnMetrics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SubscriptionMetrics extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $metrics = app(ChurnMetrics::class);

        return [
            $this->trialConversionStat($metrics),
            $this->paidChurnStat($metrics),
            $this->avgLifetimeStat($metrics),
        ];
    }

    private function trialConversionStat(ChurnMetrics $metrics): Stat
    {
        $data = $metrics->trialConversion();

        return Stat::make('Trial Conversion Rate', "{$data->rate}%")
            ->description("{$data->converted} converted, {$data->notConverted} churned, {$data->inTrial} in trial");
    }

    private function paidChurnStat(ChurnMetrics $metrics): Stat
    {
        $data = $metrics->paidChurn();

        return Stat::make('Paid Churn Rate', "{$data->rate}%")
            ->description("{$data->churned} churned, {$data->active} active out of {$data->total} paid");
    }

    private function avgLifetimeStat(ChurnMetrics $metrics): Stat
    {
        $data = $metrics->avgLifetime();

        return Stat::make('Avg Churned Subscriber Lifetime', "{$data->avg_charges} charges")
            ->description("Min {$data->min_charges}, Max {$data->max_charges} across {$data->n} churned subscribers");
    }
}