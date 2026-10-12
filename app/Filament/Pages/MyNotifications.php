<?php

namespace App\Filament\Pages;

use App\Models\Admin;
use App\Models\AdminNotificationPreference;
use App\Notifications\NotificationChannel;
use App\Notifications\NotificationType;
use App\Support\PushDevices;
use App\Support\SmsSubscriptions;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Each admin's own staff-alert choices (ai/modules/will-call-notifications,
 * phase 2). Everything is on until an admin turns it off. Reached from the
 * user menu, not the sidebar.
 */
class MyNotifications extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'My notifications';

    protected static ?string $slug = 'my-notifications';

    protected string $view = 'filament.pages.my-notifications';

    public ?array $prefs = [];

    // Text alerts sign-up (phase 6).
    public string $smsPhone = '';

    public bool $smsConsent = false;

    public string $smsCode = '';

    public static function canAccess(): bool
    {
        return Filament::auth()->user() instanceof Admin;
    }

    public function mount(): void
    {
        $muted = AdminNotificationPreference::mutedKeys($this->admin());
        $state = [];

        foreach (self::cells() as [$type, $channel]) {
            $state[self::key($type, $channel)] = ! in_array("{$type->value}:{$channel->value}", $muted, true);
        }

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(collect(NotificationType::forAdmins())
                ->map(fn (NotificationType $type) => Section::make($type->label())
                    ->description($type->description())
                    ->icon($type->icon())
                    ->compact()
                    ->columns(['default' => 2, 'md' => 4])
                    ->schema(collect($type->defaultChannels())
                        ->map(fn (NotificationChannel $channel) => Toggle::make(self::key($type, $channel))
                            ->label($channel->label())
                            ->disabled(! $channel->isReady())
                            ->helperText(match (true) {
                                ! $channel->isReady() => 'Coming soon',
                                $channel === NotificationChannel::Push => 'On devices you turn on below',
                                $channel === NotificationChannel::Sms => 'To the number you verify below',
                                default => null,
                            }))
                        ->all()))
                ->all())
            ->statePath('prefs');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('muteAllEmail')
                ->label('Turn off all email')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->action(function () {
                    $state = $this->form->getRawState();

                    foreach (self::cells() as [$type, $channel]) {
                        if ($channel === NotificationChannel::Email) {
                            $state[self::key($type, $channel)] = false;
                        }
                    }

                    $this->form->fill($state);

                    Notification::make()->title('Email turned off for every alert. Save to keep it.')->info()->send();
                }),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $admin = $this->admin();

        DB::transaction(function () use ($state, $admin) {
            foreach (self::cells() as [$type, $channel]) {
                // Channels that aren't live yet can't be changed here.
                if (! $channel->isReady()) {
                    continue;
                }

                $enabled = (bool) ($state[self::key($type, $channel)] ?? true);

                if ($enabled) {
                    AdminNotificationPreference::query()
                        ->where('admin_id', $admin->id)
                        ->where('type', $type)
                        ->where('channel', $channel)
                        ->delete();
                } else {
                    AdminNotificationPreference::query()->updateOrCreate(
                        ['admin_id' => $admin->id, 'type' => $type, 'channel' => $channel],
                        ['enabled' => false],
                    );
                }
            }
        });

        Notification::make()->title('Notification choices saved')->success()->send();
    }

    /**
     * Devices this admin turned push on for (shown under the form).
     *
     * @return list<array{id: int, label: string, endpoint: string, added: ?string}>
     */
    public function getPushDevices(): array
    {
        return PushDevices::list($this->admin());
    }

    public function pushPublicKey(): ?string
    {
        return NotificationChannel::Push->isReady() ? config('webpush.vapid.public_key') : null;
    }

    /**
     * Called from the browser after it subscribed (see the page view).
     */
    public function smsReady(): bool
    {
        return NotificationChannel::Sms->isReady();
    }

    /**
     * @return array{state: string, phone?: string}
     */
    public function getSmsStatus(): array
    {
        return SmsSubscriptions::status($this->admin());
    }

    public function startSms(): void
    {
        abort_unless($this->smsReady(), 404);

        SmsSubscriptions::start($this->admin(), $this->smsPhone, $this->smsConsent, request()->ip());
        $this->reset('smsCode');

        Notification::make()->title('Code sent')->body('Enter the 6-digit code we texted you.')->success()->send();
    }

    public function verifySms(): void
    {
        abort_unless($this->smsReady(), 404);

        SmsSubscriptions::verify($this->admin(), $this->smsCode);
        $this->reset('smsCode', 'smsPhone', 'smsConsent');

        Notification::make()->title('Text alerts are on')->success()->send();
    }

    public function stopSms(): void
    {
        SmsSubscriptions::stop($this->admin());
        $this->reset('smsCode', 'smsPhone', 'smsConsent');

        Notification::make()->title('Text alerts are off')->success()->send();
    }

    public function savePushSubscription(array $subscription): void
    {
        PushDevices::save($this->admin(), PushDevices::validate($subscription));

        Notification::make()->title('Notifications turned on for this device')->success()->send();
    }

    public function removePushDevice(?int $id = null, ?string $endpoint = null): void
    {
        PushDevices::remove($this->admin(), $id, $endpoint);
    }

    /**
     * Every admin type × channel it can use.
     *
     * @return list<array{0: NotificationType, 1: NotificationChannel}>
     */
    private static function cells(): array
    {
        $cells = [];

        foreach (NotificationType::forAdmins() as $type) {
            foreach ($type->defaultChannels() as $channel) {
                $cells[] = [$type, $channel];
            }
        }

        return $cells;
    }

    private static function key(NotificationType $type, NotificationChannel $channel): string
    {
        return "{$type->value}__{$channel->value}";
    }

    private function admin(): Admin
    {
        return Filament::auth()->user();
    }
}
