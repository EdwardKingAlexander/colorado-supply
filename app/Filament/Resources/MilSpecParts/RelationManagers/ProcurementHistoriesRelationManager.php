<?php

namespace App\Filament\Resources\MilSpecParts\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProcurementHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'procurementHistories';

    protected static ?string $title = 'Procurement History';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Procurement Details')
                ->columns(2)
                ->schema([
                    Select::make('supplier_id')
                        ->label('Supplier')
                        ->relationship('supplier', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    TextInput::make('price')
                        ->numeric()
                        ->prefix('$')
                        ->required(),
                    TextInput::make('quantity')
                        ->numeric()
                        ->minValue(1)
                        ->required(),
                    DatePicker::make('acquisition_date')
                        ->label('Acquisition Date')
                        ->required(),
                ]),
            Section::make('Source & Notes')
                ->schema([
                    TextInput::make('source_url')
                        ->label('Source URL')
                        ->url()
                        ->required()
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->rows(3)
                        ->maxLength(65535)
                        ->columnSpanFull()
                        ->nullable(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('acquisition_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('source_url')
                    ->label('Source')
                    ->url(fn ($record) => $record->source_url)
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
