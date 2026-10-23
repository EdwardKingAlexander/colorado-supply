<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Decides which Inertia pages follow the signed-in customer's theme
 * preference (Light / Dark / System, stored per browser). Every other page —
 * marketing, auth, maintenance — stays forced dark.
 *
 * See ai/modules/customer-portal-ui-rebrand-complete.
 */
class PortalTheme
{
    /** Component name prefixes rendered inside the shared store shell. */
    private const PREFIXES = [
        'Dashboard/',
        'Profile/',
        'Company/',
        'Notifications/',
        'Sam/',
        'Store/',
    ];

    /** Store components that render on the public chrome instead. */
    private const EXCLUDED = [
        'Store/Unavailable',
    ];

    public static function appliesTo(?string $component): bool
    {
        if (blank($component) || in_array($component, self::EXCLUDED, true)) {
            return false;
        }

        return $component === 'Dashboard' || Str::startsWith($component, self::PREFIXES);
    }
}
