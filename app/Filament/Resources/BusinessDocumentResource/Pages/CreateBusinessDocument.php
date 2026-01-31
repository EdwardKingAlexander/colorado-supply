<?php

namespace App\Filament\Resources\BusinessDocumentResource\Pages;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Filament\Resources\BusinessDocumentResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\CreateRecord;

class CreateBusinessDocument extends CreateRecord
{
    protected static string $resource = BusinessDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('preset_ein')
                    ->label('EIN Letter')
                    ->icon('heroicon-o-document-text')
                    ->action(fn () => $this->fillPreset([
                        'type' => DocumentType::TaxDocument->value,
                        'name' => 'EIN Confirmation Letter',
                        'description' => 'IRS Employer Identification Number assignment letter',
                        'issuing_authority' => 'Internal Revenue Service',
                        'status' => DocumentStatus::Active->value,
                    ])),

                Action::make('preset_sales_tax')
                    ->label('Sales Tax License')
                    ->icon('heroicon-o-receipt-percent')
                    ->action(fn () => $this->fillPreset([
                        'type' => DocumentType::License->value,
                        'name' => 'Colorado Sales Tax License',
                        'description' => 'State sales tax license for retail and wholesale',
                        'issuing_authority' => 'Colorado Department of Revenue',
                        'status' => DocumentStatus::Active->value,
                    ])),

                Action::make('preset_sam')
                    ->label('SAM.gov Registration')
                    ->icon('heroicon-o-star')
                    ->action(fn () => $this->fillPreset([
                        'type' => DocumentType::Registration->value,
                        'name' => 'SAM.gov Registration',
                        'description' => 'System for Award Management registration for federal contracting',
                        'issuing_authority' => 'System for Award Management',
                        'status' => DocumentStatus::Active->value,
                    ])),

                Action::make('preset_liability_insurance')
                    ->label('General Liability Insurance')
                    ->icon('heroicon-o-shield-check')
                    ->action(fn () => $this->fillPreset([
                        'type' => DocumentType::Insurance->value,
                        'name' => 'General Liability Insurance',
                        'description' => 'Commercial general liability insurance policy',
                        'status' => DocumentStatus::Active->value,
                    ])),

                Action::make('preset_workers_comp')
                    ->label('Workers Comp Insurance')
                    ->icon('heroicon-o-user-group')
                    ->action(fn () => $this->fillPreset([
                        'type' => DocumentType::Insurance->value,
                        'name' => 'Workers Compensation Insurance',
                        'description' => 'Workers compensation coverage for employees',
                        'status' => DocumentStatus::Active->value,
                    ])),

                Action::make('preset_business_license')
                    ->label('Business License')
                    ->icon('heroicon-o-building-storefront')
                    ->action(fn () => $this->fillPreset([
                        'type' => DocumentType::License->value,
                        'name' => 'Business License',
                        'description' => 'Local business operating license',
                        'status' => DocumentStatus::Active->value,
                    ])),
            ])
                ->label('Use Template')
                ->icon('heroicon-o-document-duplicate')
                ->button()
                ->color('gray'),
        ];
    }

    protected function fillPreset(array $data): void
    {
        $this->form->fill($data);
    }
}
