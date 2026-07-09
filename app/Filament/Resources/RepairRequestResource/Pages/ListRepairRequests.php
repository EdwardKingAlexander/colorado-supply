<?php

namespace App\Filament\Resources\RepairRequestResource\Pages;

use App\Filament\Resources\RepairRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListRepairRequests extends ListRecords
{
    protected static string $resource = RepairRequestResource::class;

    protected function getHeaderActions(): array
    {
        // No "Create"—these come from the public site
        return [];
    }
}
