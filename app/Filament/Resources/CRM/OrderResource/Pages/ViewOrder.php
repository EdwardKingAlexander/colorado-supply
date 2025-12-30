<?php

namespace App\Filament\Resources\CRM\OrderResource\Pages;

use App\Filament\Resources\CRM\OrderResource;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
