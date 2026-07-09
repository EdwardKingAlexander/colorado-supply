<?php

namespace App\Filament\Resources\RepairRequestResource\Pages;

use App\Filament\Resources\RepairRequestResource;
use Filament\Resources\Pages\ViewRecord;

class ViewRepairRequest extends ViewRecord
{
    protected static string $resource = RepairRequestResource::class;

    protected function getHeaderActions(): array
    {
        // View only; you can mark handled from the table actions
        return [];
    }
}
