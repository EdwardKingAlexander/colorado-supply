<?php

namespace App\Filament\Resources\ScrapedProducts;

use App\Filament\Resources\ScrapedProducts\Pages\CreateScrapedProduct;
use App\Filament\Resources\ScrapedProducts\Pages\EditScrapedProduct;
use App\Filament\Resources\ScrapedProducts\Pages\ListScrapedProducts;
use App\Filament\Resources\ScrapedProducts\Schemas\ScrapedProductForm;
use App\Filament\Resources\ScrapedProducts\Tables\ScrapedProductsTable;
use App\Models\ScrapedProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ScrapedProductResource extends Resource
{
    protected static ?string $model = ScrapedProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Product Import Wizard';

    protected static ?string $modelLabel = 'Scraped Product';

    protected static ?string $pluralModelLabel = 'Scraped Products';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return ScrapedProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScrapedProductsTable::configure($table);
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
            'index' => ListScrapedProducts::route('/'),
            'create' => CreateScrapedProduct::route('/create'),
            'edit' => EditScrapedProduct::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
