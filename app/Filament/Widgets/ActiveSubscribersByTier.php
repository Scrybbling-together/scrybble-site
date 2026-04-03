<?php

namespace App\Filament\Widgets;

use App\Enums\SubscriberTier;
use App\Services\SubscriberBreakdown;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActiveSubscribersByTier extends TableWidget
{
    protected static ?int $sort = 1;

    protected static ?string $heading = 'Active Subscribers by Tier';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $sub = app(SubscriberBreakdown::class)->byTierAndRecurrenceQuery();

        $tierLabels = collect(SubscriberTier::cases())
            ->mapWithKeys(fn (SubscriberTier $t) => [$t->value => $t->label()]);

        return $table
            ->query(fn (): Builder => (new class extends Model {
                protected $table = 'tier_stats';
                protected $primaryKey = 'id';
                public $incrementing = false;
                protected $keyType = 'string';
            })::query()->fromSub($sub, 'tier_stats'))
            ->defaultSort('sort_order', 'asc')
            ->columns([
                TextColumn::make('tier')
                    ->label('Tier')
                    ->formatStateUsing(fn (string $state) => $tierLabels->get($state, $state))
                    ->weight(fn ($record) => $record->tier === 'Total' ? 'bold' : null),
                TextColumn::make('trial')
                    ->label('Trial')
                    ->alignEnd(),
                TextColumn::make('monthly')
                    ->label('Monthly')
                    ->alignEnd(),
                TextColumn::make('yearly')
                    ->label('Yearly')
                    ->alignEnd(),
                TextColumn::make('two_yearly')
                    ->label('Two-yearly')
                    ->alignEnd(),
                TextColumn::make('total_active')
                    ->label('Total Active')
                    ->alignEnd()
                    ->weight('bold'),
                TextColumn::make('total')
                    ->label('Total')
                    ->alignEnd()
                    ->weight('bold'),
            ])
            ->paginated(false);
    }
}