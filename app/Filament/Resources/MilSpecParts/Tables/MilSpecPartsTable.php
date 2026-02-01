<?php

namespace App\Filament\Resources\MilSpecParts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MilSpecPartsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nsn')
                    ->label('NSN')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('NSN copied'),
                TextColumn::make('description')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),
                TextColumn::make('manufacturer_part_number')
                    ->label('Mfr Part #')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('manufacturer.name')
                    ->label('Manufacturer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('suppliers_count')
                    ->label('Suppliers')
                    ->counts('suppliers')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('procurement_histories_count')
                    ->label('History')
                    ->counts('procurementHistories')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('manufacturer')
                    ->relationship('manufacturer', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
