<?php

namespace App\Filament\Resources\BusinessDeadlineResource\Pages;

use App\Filament\Resources\BusinessDeadlineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBusinessDeadlines extends ListRecords
{
    protected static string $resource = BusinessDeadlineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
