<?php

namespace App\Notifications;

use App\Models\Admin;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Base for every store notification (ai/modules/will-call-notifications).
 *
 * The type decides who may receive it and on which channels
 * (NotificationChannels); subclasses supply the words and the link. Every
 * stored notification has the same core payload so any bell can render it.
 * Admins additionally get Filament's format so the admin panel bell and
 * toasts render it natively.
 */
abstract class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    abstract public function type(): NotificationType;

    /** Short headline, e.g. "Your order is ready for pickup". */
    abstract public function title(object $notifiable): string;

    /** One or two sentences under the headline. */
    abstract public function body(object $notifiable): string;

    /** Where clicking the notification goes. */
    public function url(object $notifiable): ?string
    {
        return null;
    }

    public function via(object $notifiable): array
    {
        return NotificationChannels::for($notifiable, $this->type());
    }

    /**
     * @return array{type: string, title: string, body: string, url: ?string, icon: string, label: string}
     */
    public function toArray(object $notifiable): array
    {
        return array_merge($this->extraData($notifiable), [
            'type' => $this->type()->value,
            'title' => $this->title($notifiable),
            'body' => $this->body($notifiable),
            'url' => $this->url($notifiable),
            'icon' => $this->type()->icon(),
            // Read by the original order bell (NotificationController).
            'label' => $this->title($notifiable),
        ]);
    }

    public function toDatabase(object $notifiable): array
    {
        if ($notifiable instanceof Admin) {
            return array_merge($this->toArray($notifiable), $this->toFilament($notifiable)->getDatabaseMessage());
        }

        return $this->toArray($notifiable);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        if ($notifiable instanceof Admin) {
            return $this->toFilament($notifiable)->getBroadcastMessage();
        }

        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * The event name buyers' browsers receive is the notification type, not
     * the PHP class name.
     */
    public function broadcastType(): string
    {
        return $this->type()->value;
    }

    protected function toFilament(object $notifiable): FilamentNotification
    {
        $url = $this->url($notifiable);

        return FilamentNotification::make()
            ->title($this->title($notifiable))
            ->body($this->body($notifiable))
            ->icon($this->type()->icon())
            ->actions($url ? [
                Action::make('view')->label('View')->url($url)->markAsRead(),
            ] : []);
    }

    /**
     * Type-specific keys stored alongside the core payload.
     */
    protected function extraData(object $notifiable): array
    {
        return [];
    }
}
