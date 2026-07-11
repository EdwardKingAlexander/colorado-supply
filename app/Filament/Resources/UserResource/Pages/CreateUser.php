<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Company;
use App\Services\Organizations\CompanyDomainService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $company = filled($data['company_id'] ?? null)
            ? Company::query()->find($data['company_id'])
            : null;

        try {
            app(CompanyDomainService::class)->assertMembershipAllowed($company, $data['email']);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([
                'data.email' => $exception->errors()['email'][0],
            ]);
        }

        return $data;
    }
}
