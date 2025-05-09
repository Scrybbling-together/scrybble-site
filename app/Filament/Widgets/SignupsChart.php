<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;

class SignupsChart extends ChartWidget
{
    protected static ?string $heading = 'Signups';

    protected function getData(): array
    {
        $data = Trend::model(User::class)->between(start: User::query()->first()->created_at, end: now())->perMonth()->count("created_at");
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

    protected function getType(): string
    {
        return 'line';
    }
}
