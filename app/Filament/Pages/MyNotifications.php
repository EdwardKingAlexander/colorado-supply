<?php

namespace App\Filament\Pages;

use App\Models\Admin;
use App\Models\AdminNotificationPreference;
use App\Notifications\NotificationChannel;
use App\Notifications\NotificationType;
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
                            ->helperText($channel->isReady() ? null : 'Coming soon'))
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
