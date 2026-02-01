<?php

namespace App\Filament\Resources\MilSpecParts\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SuppliersRelationManager extends RelationManager
{
    protected static string $relationship = 'suppliers';

    protected static ?string $title = 'Suppliers';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Supplier Details')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('cage_code')
                        ->label('CAGE Code')
                        ->maxLength(255)
                        ->nullable(),
                    TextInput::make('website')
                        ->url()
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->nullable(),
                    Textarea::make('contact_info')
                        ->label('Contact Info')
                        ->maxLength(65535)
                        ->columnSpanFull()
                        ->nullable(),
                ]),
            Section::make('Supplier Relationship')
                ->schema([
                    TextInput::make('supplier_part_number')
                        ->label('Supplier Part Number')
                        ->maxLength(255)
                        ->nullable(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cage_code')
                    ->label('CAGE Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pivot.supplier_part_number')
                    ->label('Supplier Part #')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('website')
                    ->url(fn ($record) => $record->website)
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Attach Supplier')
                    ->recordSelectSearchColumns(['name', 'cage_code'])
                    ->preloadRecordSelect()
                    ->form([
                        TextInput::make('supplier_part_number')
                            ->label('Supplier Part Number')
                            ->maxLength(255)
                            ->nullable(),
                    ]),
                CreateAction::make()
                    ->label('Create Supplier'),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
