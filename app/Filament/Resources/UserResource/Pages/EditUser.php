<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Company;
use App\Services\Organizations\CompanyDomainService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
