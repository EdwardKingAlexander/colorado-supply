<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\Product;
use App\Models\ProductRelation;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Substitutes, accessories and related products shown on the storefront
 * product page (ai/modules/product-detail-page).
 */
class RelatedProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'productRelations';

    protected static ?string $title = 'Related products';

    public function form(Schema $schema): Schema
    {
        $ownerId = $this->getOwnerRecord()->getKey();

        return $schema->components([
            Select::make('type')
                ->options(ProductRelation::TYPES)
                ->default(ProductRelation::RELATED)
                ->required()
                ->native(false),

            Select::make('related_product_id')
                ->label('Product')
                ->relationship(
                    'relatedProduct',
                    'name',
                    fn (Builder $query) => $query->whereKeyNot($ownerId),
                )
                ->getOptionLabelFromRecordUsing(fn (Product $record) => "{$record->name} ({$record->sku})")
                ->searchable(['name', 'sku'])
                ->required()
                ->rule(Rule::notIn([$ownerId]))
                ->unique(
                    ProductRelation::class,
                    'related_product_id',
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                        ->where('product_id', $ownerId)
                        ->where('type', $get('type')),
                )
                ->validationMessages([
                    'not_in' => 'A product cannot be related to itself.',
                    'unique' => 'This product is already linked with that type.',
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('relatedProduct'))
            ->columns([
                TextColumn::make('type')
                    ->formatStateUsing(fn (string $state) => ProductRelation::TYPES[$state] ?? $state)
                    ->badge()
                    ->sortable(),
                TextColumn::make('relatedProduct.name')->label('Product')->searchable(),
                TextColumn::make('relatedProduct.sku')->label('SKU')->searchable(),
            ])
            ->headerActions([
                CreateAction::make()->label('Link product'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->label('Unlink'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
