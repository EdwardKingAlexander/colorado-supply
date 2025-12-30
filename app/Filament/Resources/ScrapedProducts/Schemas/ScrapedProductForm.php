<?php

namespace App\Filament\Resources\ScrapedProducts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ScrapedProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('source_url')
                    ->url()
                    ->required(),
                TextInput::make('vendor_domain'),
                TextInput::make('title'),
                TextInput::make('sku')
                    ->label('SKU'),
                TextInput::make('price'),
                TextInput::make('price_numeric')
                    ->numeric(),
                Textarea::make('html_cache_path')
                    ->columnSpanFull(),
                TextInput::make('raw_data'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                Select::make('product_id')
                    ->relationship('product', 'name'),
                TextInput::make('imported_by')
                    ->numeric(),
                DateTimePicker::make('imported_at'),
                Textarea::make('import_notes')
                    ->columnSpanFull(),
            ]);
    }
}
