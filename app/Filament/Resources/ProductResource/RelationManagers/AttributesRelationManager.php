<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AttributesRelationManager extends RelationManager
{
    // MUST match Product::attributes()
    protected static string $relationship = 'attributes';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Attribute')
                ->required()
                ->maxLength(120),

            Forms\Components\Select::make('type')
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
            Forms\Components\TextInput::make('value')
                ->label('Display Value')
                ->maxLength(255)
                ->helperText('e.g., "12 mm", "316 SS", "Yes"'),

            // Optional numeric helper (only shows for integer/float)
            Forms\Components\TextInput::make('value_number')
                ->numeric()
                ->step('any')
                ->label('Numeric Value (optional)')
                ->helperText('Used only if you want numeric sorting/filtering later')
                ->visible(fn ($get) => in_array($get('type'), ['integer','float'])),

            // Optional boolean helper (only shows for boolean)
            Forms\Components\Toggle::make('value_bool')
                ->label('Boolean Value')
                ->visible(fn ($get) => $get('type') === 'boolean'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Attribute')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
