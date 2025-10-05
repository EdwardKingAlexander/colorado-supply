<?php

namespace App\Filament\Resources\CRM\CustomerResource\Pages;

use App\Filament\Resources\CRM\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
