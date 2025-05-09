<?php

namespace App\Filament\Widgets;

use App\Models\User;
use DB;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class SignupsChart extends ChartWidget
{
    protected static ?string $heading = 'Chart';

    protected function getData(): array
    {
        $data = Trend::model(User::class)->between(start: User::query()->first()->created_at, end: now())->perMonth()->count();
        return [
            "datasets" => [
                [
                    'label' => "Signups",
                    'data' => $data->map(fn (TrendValue $value) => ["x" => $value->date, "y" => $value->aggregate])
                ]
            ],
        ];
    }

    protected function getType(): string
    {
        return 'scatter';
    }
}
