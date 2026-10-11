<?php

namespace App\Support;

class StoreAvailability
{
    protected const KEY = 'store-settings';

    public const CONTACT_EMAIL = 'Edward@cogovsupply.com';

    /**
     * Whether the customer storefront is open (defaults to open when unset).
     */
    public static function isEnabled(): bool
    {
        return (bool) (McpSettings::for(self::KEY, ['enabled' => true])['enabled'] ?? true);
    }

    /**
     * Open or close the customer storefront. Admins can always browse it.
     */
    public static function set(bool $enabled): void
    {
        McpSettings::put(self::KEY, ['enabled' => $enabled], 'Store availability settings');
    }

    /**
     * Props for the customer-facing Store/Unavailable ("Under Maintenance") page.
     *
     * @return array<string, string>
     */
    public static function maintenancePageProps(): array
    {
        return ['contactEmail' => self::CONTACT_EMAIL];
    }
}
