<?php

namespace App\Filament\Resources\ScrapedProducts\Pages;

use App\Filament\Resources\ScrapedProducts\ScrapedProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateScrapedProduct extends CreateRecord
{
    protected static string $resource = ScrapedProductResource::class;
}
