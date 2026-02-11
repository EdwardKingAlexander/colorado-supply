<?php

namespace App\Filament\Resources\MilSpecParts\Pages;

use App\Filament\Resources\MilSpecParts\MilSpecPartResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMilSpecPart extends CreateRecord
{
    protected static string $resource = MilSpecPartResource::class;

    protected string $view = 'filament.resources.mil-spec-parts.pages.create-mil-spec-part';
}
