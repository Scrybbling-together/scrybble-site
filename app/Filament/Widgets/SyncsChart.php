<?php

namespace App\Filament\Widgets;

use App\Models\Sync;
use Filament\Widgets\ChartWidget;

class SyncsChart extends ChartWidget
{
    protected ?string $heading = 'Daily Syncs';

    protected function getData(): array
    {
        $data = Sync::selectRaw('DATE(created_at) as day, COUNT(*) as syncs_count')
            ->whereNotNull('created_at')
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at) ASC')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Syncs per day',
                    'data' => $data->pluck('syncs_count')->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
            ],
            'labels' => $data->pluck('day')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
