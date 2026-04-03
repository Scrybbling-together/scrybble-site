<?php

namespace App\Filament\Widgets;

use App\Models\Sync;

class SyncsChart extends ExportableChartWidget
{
    protected ?string $heading = 'Daily Syncs';

    private function query()
    {
        return Sync::selectRaw('DATE(created_at) as day, COUNT(*) as syncs_count')
            ->whereNotNull('created_at')
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at) ASC')
            ->get();
    }

    protected function getData(): array
    {
        $data = $this->query();

        return [
            'datasets' => [
                [
                    'label' => 'Syncs per day',
                    'data' => $data->pluck('syncs_count')->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ]
            ],
            'labels' => $data->pluck('day')->toArray(),
        ];
    }

    protected function getExportData(): array
    {
        $data = $this->query();

        return [
            'headers' => ['Date', 'Syncs'],
            'rows' => $data->map(fn ($row) => [$row->day, $row->syncs_count])->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}