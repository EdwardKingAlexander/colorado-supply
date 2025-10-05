<?php

namespace App\Filament\Resources\Admin\PermissionResource\Pages;

use App\Filament\Resources\Admin\PermissionResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePermission extends CreateRecord
{
    protected static string $resource = PermissionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
