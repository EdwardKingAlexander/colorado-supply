<?php

namespace App\Filament\Resources\CRM\QuoteResource\Pages;

use App\Filament\Resources\CRM\QuoteResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewQuote extends ViewRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            QuoteResource::convertToOrderAction(),
        ];
    }
}
