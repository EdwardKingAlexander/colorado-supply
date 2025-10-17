<?php

namespace App\Filament\Resources\CRM\QuoteResource\Pages;

use App\Filament\Resources\CRM\QuoteResource;
use App\Services\QuoteTotalsService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Set is_walk_in toggle state
        $data['is_walk_in'] = empty($data['customer_id']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure walk-in fields are null if customer is selected
        if (!empty($data['customer_id'])) {
            $data['walk_in_label'] = 'cash/card';
            $data['walk_in_org'] = null;
            $data['walk_in_contact_name'] = null;
            $data['walk_in_email'] = null;
            $data['walk_in_phone'] = null;
            $data['walk_in_billing_json'] = null;
            $data['walk_in_shipping_json'] = null;
        } else {
            // Validate walk-in fields
            $this->validate([
                'data.walk_in_contact_name' => 'required|string|max:255',
                'data.walk_in_label' => 'required|string|max:255',
            ]);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Recalculate totals after save
        $totalsService = app(QuoteTotalsService::class);
        $totalsService->recalculateTotals($this->record);
    }
}
