<?php

namespace App\Filament\Resources\CRM\QuoteResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Line Items')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Description')
                    ->wrap()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\TextColumn::make('line_total')
                    ->label('Line Total')
                    ->money('usd')
                    ->sortable(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
