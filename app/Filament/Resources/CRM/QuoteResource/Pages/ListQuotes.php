<?php

namespace App\Filament\Resources\CRM\QuoteResource\Pages;

use App\Filament\Resources\CRM\QuoteResource;
use App\Filament\Resources\CRM\QuoteResource\Widgets\QuoteStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuotes extends ListRecords
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            QuoteStatsWidget::class,
        ];
    }
}
