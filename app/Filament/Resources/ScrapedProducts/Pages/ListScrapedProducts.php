<?php

namespace App\Filament\Resources\ScrapedProducts\Pages;

use App\Filament\Resources\ScrapedProducts\ScrapedProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScrapedProducts extends ListRecords
{
    protected static string $resource = ScrapedProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
