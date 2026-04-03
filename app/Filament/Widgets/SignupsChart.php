<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class SignupsChart extends ExportableChartWidget
{
    protected ?string $heading = 'Signups';

    private function query()
    {
        return Trend::model(User::class)->between(start: User::query()->first()->created_at, end: now())->perMonth()->count("created_at");
    }

    protected function getData(): array
    {
        $data = $this->query();
        return [
            "datasets" => [
                [
                    'label' => "Signups per month",
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate)
                ]
            ],
            "labels" => $data->map(fn (TrendValue $value) => $value->date)
        ];
    }

    protected function getExportData(): array
    {
        $data = $this->query();

        return [
            'headers' => ['Month', 'Signups'],
            'rows' => $data->map(fn (TrendValue $value) => [$value->date, $value->aggregate])->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
