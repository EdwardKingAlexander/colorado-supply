<?php

namespace App\Filament\Resources\Suppliers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cage_code')
                    ->label('CAGE Code')
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'primary' : 'gray')
                    ->fontFamily(FontFamily::Mono)
                    ->placeholder('--')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contact_info')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    })
                    ->toggleable(isToggledHiddenByDefault: true), // Hidden by default
                TextColumn::make('website')
                    ->placeholder('--')
                    ->url(function ($record): ?string {
                        $website = trim((string) $record->website);

                        if ($website === '') {
                            return null;
                        }

                        if (! preg_match('/^https?:\/\//i', $website)) {
                            $website = "https://{$website}";
                        }

                        return $website;
                    })
                    ->openUrlInNewTab()
                    ->searchable()
                    ->sortable(),
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
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
