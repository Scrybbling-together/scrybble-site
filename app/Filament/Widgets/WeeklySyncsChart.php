<?php

namespace App\Filament\Widgets;

use App\Models\Sync;
use Filament\Widgets\ChartWidget;

class WeeklySyncsChart extends ChartWidget
{
    protected ?string $heading = 'Weekly Syncs';

    protected function getData(): array
    {
        $data = Sync::selectRaw("DATE_FORMAT(created_at, '%V-%X') as week, COUNT(*) as syncs_count")
            ->whereNotNull('created_at')
            ->groupByRaw("DATE_FORMAT(created_at, '%V-%X')")
            ->orderByRaw("DATE_FORMAT(created_at, '%V-%X') ASC")
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Syncs per week',
                    'data' => $data->pluck('syncs_count')->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
            ],
            'labels' => $data->pluck('week')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
