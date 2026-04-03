<?php

namespace App\Filament\Widgets;

use App\Enums\SubscriptionPeriod;
use App\Services\SubscriberBreakdown;

class NewMonthlySubscribersChart extends ExportableChartWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'New Paying Monthly Subscribers per Month';

    private function query()
    {
        return app(SubscriberBreakdown::class)->newSubscribersPerMonth(SubscriptionPeriod::Monthly);
    }

    protected function getData(): array
    {
        $data = $this->query();

        return [
            'datasets' => [
                [
                    'label' => 'New monthly subscribers',
                    'data' => $data->pluck('cnt')->toArray(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
            ],
            'labels' => $data->pluck('month')->toArray(),
        ];
    }

    protected function getExportData(): array
    {
        $data = $this->query();

        return [
            'headers' => ['Month', 'New Subscribers'],
            'rows' => $data->map(fn ($row) => [$row->month, $row->cnt])->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}