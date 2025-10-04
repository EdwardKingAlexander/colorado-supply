<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AttributesRelationManager extends RelationManager
{
    // MUST match Product::attributes()
    protected static string $relationship = 'attributes';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Attribute')
                ->required()
                ->maxLength(120),

            Select::make('type')
                ->label('Type')
                ->options([
                    'string'  => 'String',
                    'integer' => 'Integer',
                    'float'   => 'Float',
                    'boolean' => 'Boolean',
                    'select'  => 'Select',
                ])
                ->default('string')
                ->required()
                ->native(false),

            // Plain display value (always allowed)
            TextInput::make('value')
                ->label('Display Value')
                ->maxLength(255)
                ->helperText('e.g., "12 mm", "316 SS", "Yes"'),

            // Optional numeric helper (only shows for integer/float)
            TextInput::make('value_number')
                ->numeric()
                ->step('any')
                ->label('Numeric Value (optional)')
                ->helperText('Used only if you want numeric sorting/filtering later')
                ->visible(fn ($get) => in_array($get('type'), ['integer','float'])),

            // Optional boolean helper (only shows for boolean)
            Toggle::make('value_bool')
                ->label('Boolean Value')
                ->visible(fn ($get) => $get('type') === 'boolean'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Attribute')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('value')
                    ->label('Value')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
