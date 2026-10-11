<?php

namespace App\Filament\Widgets;

use App\Support\StoreAvailability;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Throwable;

/**
 * Admin dashboard on/off switch for the customer storefront. Writes the same
 * flag as McpDashboard's `toggleStore` action and the profile-menu item in
 * AdminPanelProvider, so all three controls always agree.
 */
class StoreStatusWidget extends Widget
{
    protected static ?int $sort = -10;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.store-status-widget';

    public bool $storeEnabled = true;

    public function mount(): void
    {
        $this->storeEnabled = StoreAvailability::isEnabled();
    }

    public function updatedStoreEnabled(bool $value): void
    {
        try {
            StoreAvailability::set($value);

            Notification::make()
                ->title($value ? 'Store Open' : 'Store Closed')
                ->body($value
                    ? 'Customers can browse and order again.'
                    : 'Customers now see the Under Maintenance page. Admins can still browse the store.')
                ->success()
                ->send();
        } catch (Throwable $e) {
            $this->storeEnabled = StoreAvailability::isEnabled();

            Notification::make()
                ->title('Could not change store status')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
