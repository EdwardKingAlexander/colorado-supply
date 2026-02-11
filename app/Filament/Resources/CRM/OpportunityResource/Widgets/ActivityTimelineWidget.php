<?php

namespace App\Filament\Resources\CRM\OpportunityResource\Widgets;

use App\Models\Opportunity;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class ActivityTimelineWidget extends BaseWidget
{
    public ?Model $record = null;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        if (!$this->record instanceof Opportunity) {
            return $table->query(fn() => null);
        }

        return $table
            ->heading('Activity Timeline')
            ->query(
                fn() => $this->record->activities()->latest('created_at')
            )
            ->columns([
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => ['call', 'task'],
                        'success' => 'email',
                        'warning' => 'meeting',
                        'gray' => 'note',
                    ]),

                Tables\Columns\TextColumn::make('subject')
                    ->weight('medium')
                    ->searchable(),

                Tables\Columns\TextColumn::make('body')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->body)
                    ->wrap(),

                Tables\Columns\TextColumn::make('owner.name')
                    ->label('By'),

                Tables\Columns\IconColumn::make('done_at')
                    ->label('Done')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn($record) => $record->done_at !== null),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->since(),
            ])
            ->paginated([5, 10, 25]);
    }
}
