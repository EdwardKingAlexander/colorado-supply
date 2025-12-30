<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductAttributeResource\Pages\CreateProductAttribute;
use App\Filament\Resources\ProductAttributeResource\Pages\EditProductAttribute;
use App\Filament\Resources\ProductAttributeResource\Pages\ListProductAttributes;
use App\Models\ProductAttribute;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class ProductAttributeResource extends Resource
{
    protected static ?string $model = ProductAttribute::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->label('Attribute Name')
                    ->required()
                    ->maxLength(120)
                    ->rule(function (callable $get, ?ProductAttribute $record) {
                        $productId = $get('product_id');

                        if (! $productId) {
                            return null;
                        }

                        $rule = Rule::unique(ProductAttribute::class, 'name')
                            ->where('product_id', $productId);

                        if ($record) {
                            $rule->ignore($record->getKey());
                        }

                        return $rule;
                    }),
                Select::make('type')
                    ->label('Data Type')
                    ->options(self::getAttributeTypeOptions())
                    ->default('string')
                    ->native(false)
                    ->required(),
                TextInput::make('value')
                    ->label('Default / Example Value')
                    ->maxLength(255)
                    ->helperText('Optional helper value that illustrates how this attribute should be filled.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                TextColumn::make('name')
                    ->label('Attribute')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'string' => 'primary',
                        'integer' => 'warning',
                        'float' => 'info',
                        'boolean' => 'success',
                        'select' => 'purple',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Value / Example')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y g:i a')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('product')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable(),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(self::getAttributeTypeOptions()),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductAttributes::route('/'),
            'create' => CreateProductAttribute::route('/create'),
            'edit' => EditProductAttribute::route('/{record}/edit'),
        ];
    }

    protected static function getAttributeTypeOptions(): array
    {
        return [
            'string' => 'Text',
            'integer' => 'Integer',
            'float' => 'Decimal',
            'boolean' => 'Boolean',
            'select' => 'Select',
        ];
    }
}
