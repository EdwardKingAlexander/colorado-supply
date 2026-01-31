<?php

namespace App\Filament\Resources\BusinessDeadlineResource\Pages;

use App\Enums\DeadlineCategory;
use App\Enums\RecurrenceType;
use App\Filament\Resources\BusinessDeadlineResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\CreateRecord;

class CreateBusinessDeadline extends CreateRecord
{
    protected static string $resource = BusinessDeadlineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('preset_quarterly_tax')
                    ->label('Quarterly Tax (941)')
                    ->icon('heroicon-o-calculator')
                    ->action(fn () => $this->fillPreset([
                        'title' => 'Federal Quarterly Tax (Form 941)',
                        'description' => 'File Form 941 for quarterly federal tax return',
                        'category' => DeadlineCategory::Tax->value,
                        'recurrence' => RecurrenceType::Quarterly->value,
                        'reminder_days' => ['30', '14', '7', '1'],
                        'external_url' => 'https://www.eftps.gov',
                    ])),

                Action::make('preset_sales_tax')
                    ->label('Monthly Sales Tax')
                    ->icon('heroicon-o-receipt-percent')
                    ->action(fn () => $this->fillPreset([
                        'title' => 'Colorado Sales Tax Filing',
                        'description' => 'File monthly Colorado sales tax return',
                        'category' => DeadlineCategory::Tax->value,
                        'recurrence' => RecurrenceType::Monthly->value,
                        'reminder_days' => ['14', '7', '3', '1'],
                        'external_url' => 'https://www.colorado.gov/revenueonline',
                    ])),

                Action::make('preset_annual_report')
                    ->label('Annual Report')
                    ->icon('heroicon-o-document-text')
                    ->action(fn () => $this->fillPreset([
                        'title' => 'Colorado Annual Report',
                        'description' => 'File annual report with Colorado Secretary of State',
                        'category' => DeadlineCategory::Registration->value,
                        'recurrence' => RecurrenceType::Annually->value,
                        'reminder_days' => ['60', '30', '14', '7'],
                        'external_url' => 'https://www.sos.state.co.us',
                    ])),

                Action::make('preset_sam_renewal')
                    ->label('SAM.gov Renewal')
                    ->icon('heroicon-o-star')
                    ->action(fn () => $this->fillPreset([
                        'title' => 'SAM.gov Registration Renewal',
                        'description' => 'Renew SAM.gov registration for federal contracting eligibility',
                        'category' => DeadlineCategory::Registration->value,
                        'recurrence' => RecurrenceType::Annually->value,
                        'reminder_days' => ['60', '30', '14', '7'],
                        'external_url' => 'https://sam.gov',
                    ])),

                Action::make('preset_insurance_renewal')
                    ->label('Insurance Renewal')
                    ->icon('heroicon-o-shield-check')
                    ->action(fn () => $this->fillPreset([
                        'title' => 'Insurance Policy Renewal',
                        'description' => 'Renew business insurance policy before expiration',
                        'category' => DeadlineCategory::Compliance->value,
                        'recurrence' => RecurrenceType::Annually->value,
                        'reminder_days' => ['60', '30', '14', '7'],
                    ])),

                Action::make('preset_license_renewal')
                    ->label('License Renewal')
                    ->icon('heroicon-o-identification')
                    ->action(fn () => $this->fillPreset([
                        'title' => 'Business License Renewal',
                        'description' => 'Renew business operating license',
                        'category' => DeadlineCategory::LicenseRenewal->value,
                        'recurrence' => RecurrenceType::Annually->value,
                        'reminder_days' => ['60', '30', '14', '7'],
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
