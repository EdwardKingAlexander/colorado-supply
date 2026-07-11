<?php

namespace App\Providers\Filament;

use App\Support\EmailVerificationSettings;
use App\Support\McpSettings;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Width;
use Filament\Widgets;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Throwable;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->authGuard('admin')
            ->sidebarCollapsibleOnDesktop()
            ->login()
            ->colors([
                'primary' => '#0277bd', // terminal cyan-blue
                'secondary' => '#4b5563', // gray-600
                'accent' => '#00e5ff', // terminal cyan
                'success' => '#00e676', // terminal green
                'warning' => '#ffab00', // terminal amber
                'danger' => '#ff1744', // terminal red
            ])
            ->darkMode(true)
            ->maxContentWidth(Width::ScreenTwoExtraLarge)
            ->multiFactorAuthentication([
                AppAuthentication::make()->recoverable(),
                EmailAuthentication::make(),
            ], isRequired: true)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
                // add more widgets here they show up on the dashboard
            ])
            ->userMenuItems([
                'toggleStoreAvailability' => static::storeToggleUserMenuAction(),
                'toggleEmailVerification' => static::emailVerificationToggleUserMenuAction(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Quick-access duplicate of McpDashboard::getHeaderActions()'s
     * `toggleStore` action, exposed in the profile dropdown so admins
     * don't have to leave the current page to flip store availability.
     * Keep both in sync if this changes.
     */
    protected static function storeToggleUserMenuAction(): Action
    {
        $isStoreEnabled = fn (): bool => McpSettings::for('store-settings', ['enabled' => true])['enabled'] ?? true;

        return Action::make('toggleStoreAvailability')
            ->label(fn () => $isStoreEnabled() ? 'Disable Store' : 'Enable Store')
            ->icon(fn () => $isStoreEnabled() ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
            ->color(fn () => $isStoreEnabled() ? 'warning' : 'success')
            ->requiresConfirmation()
            ->modalHeading(fn () => $isStoreEnabled() ? 'Disable Store?' : 'Enable Store?')
            ->modalDescription(fn () => $isStoreEnabled()
                ? 'This will make the store inaccessible to all users except admins. Regular users will see a 403 error.'
                : 'This will make the store accessible to all authenticated users again.')
            ->action(function () use ($isStoreEnabled) {
                $storeEnabled = $isStoreEnabled();

                try {
                    McpSettings::put('store-settings', ['enabled' => ! $storeEnabled], 'Store availability settings');

                    Notification::make()
                        ->title($storeEnabled ? 'Store Disabled' : 'Store Enabled')
                        ->body($storeEnabled
                            ? 'The store is now only accessible to admins.'
                            : 'The store is now accessible to all users.')
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Error')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Quick-access duplicate of McpDashboard::getHeaderActions()'s
     * `toggleEmailVerification` action, exposed in the profile dropdown so
     * admins don't have to leave the current page to flip it (primarily a
     * local-development convenience). Keep both in sync if this changes.
     */
    protected static function emailVerificationToggleUserMenuAction(): Action
    {
        $isEnabled = fn (): bool => EmailVerificationSettings::isEnabled();

        return Action::make('toggleEmailVerification')
            ->label(fn () => $isEnabled() ? 'Disable Email Verification' : 'Enable Email Verification')
            ->icon(fn () => $isEnabled() ? 'heroicon-o-envelope-open' : 'heroicon-o-envelope')
            ->color(fn () => $isEnabled() ? 'warning' : 'success')
            ->requiresConfirmation()
            ->modalHeading(fn () => $isEnabled() ? 'Disable Email Verification?' : 'Enable Email Verification?')
            ->modalDescription(fn () => $isEnabled()
                ? 'This is a GLOBAL switch: new registrations will not be asked to verify their email address, and no verification emails will be sent. Intended for local development — do not leave disabled in production.'
                : 'New registrations will be required to verify their email address before accessing account areas.')
            ->action(function () use ($isEnabled) {
                $enabled = $isEnabled();

                try {
                    EmailVerificationSettings::setEnabled(! $enabled);

                    Notification::make()
                        ->title($enabled ? 'Email Verification Disabled' : 'Email Verification Enabled')
                        ->body($enabled
                            ? 'Registrations no longer require email verification, and verification emails are muted.'
                            : 'Registrations must verify their email address again.')
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Error')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
