<?php

namespace App\Filament\Resources\CRM\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Order Items')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Item')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('line_total')
                    ->label('Line Total')
                    ->money('USD')
                    ->sortable(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
