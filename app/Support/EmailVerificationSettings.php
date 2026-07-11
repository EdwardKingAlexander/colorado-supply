<?php

namespace App\Support;

class EmailVerificationSettings
{
    protected const KEY = 'email-verification';

    /**
     * Whether email verification is currently enforced for customer users.
     */
    public static function isEnabled(): bool
    {
        return (bool) (McpSettings::for(self::KEY, ['enabled' => true])['enabled'] ?? true);
    }

    /**
     * Persist the enforcement flag (used by the admin panel toggle).
     */
    public static function setEnabled(bool $enabled): void
    {
        McpSettings::put(
            self::KEY,
            ['enabled' => $enabled],
            'Controls whether customer registrations must verify their email address.'
        );
    }
}
