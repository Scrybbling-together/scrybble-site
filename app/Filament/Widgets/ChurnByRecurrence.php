<?php

namespace App\Filament\Widgets;

use App\Services\ChurnMetrics;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChurnByRecurrence extends TableWidget
{
    protected static ?int $sort = 0;

    protected static ?string $heading = 'Avg Churned Subscriber Lifetime by Recurrence';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $sub = app(ChurnMetrics::class)->churnByRecurrenceQuery();

        return $table
            ->query(fn (): Builder => (new class extends Model {
                protected $table = 'stats';
                protected $primaryKey = 'id';
                public $incrementing = false;
            })::query()->fromSub($sub, 'stats'))
            ->defaultSort('n', 'desc')
            ->columns([
                TextColumn::make('recurrence')
                    ->label('Recurrence'),
                TextColumn::make('n')
                    ->label('Churned Subscribers'),
                TextColumn::make('avg_lifetime_charges')
                    ->label('Avg Lifetime (charges)'),
            ])
            ->paginated(false);
    }
}