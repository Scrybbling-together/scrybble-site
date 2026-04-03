<?php

namespace App\Filament\Widgets;

use App\Enums\SubscriptionPeriod;
use App\Services\SubscriberBreakdown;

class MonthlyCohortRetentionChart extends ExportableChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Monthly Subscriber Cohort Retention';

    private function query()
    {
        return app(SubscriberBreakdown::class)->cohortRetention(SubscriptionPeriod::Monthly);
    }

    protected function getData(): array
    {
        $data = $this->query();

        return [
            'datasets' => [
                [
                    'label' => 'Started',
                    'data' => $data->pluck('started')->toArray(),
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                ],
                [
                    'label' => 'Still active',
                    'data' => $data->pluck('still_active')->toArray(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
            ],
            'labels' => $data->pluck('cohort_month')->toArray(),
        ];
    }

    protected function getExportData(): array
    {
        $data = $this->query();

        return [
            'headers' => ['Cohort Month', 'Started', 'Still Active'],
            'rows' => $data->map(fn ($row) => [$row->cohort_month, $row->started, $row->still_active])->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}