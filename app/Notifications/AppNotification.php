<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Base for every store notification (ai/modules/will-call-notifications).
 *
 * The type decides who may receive it and on which channels
 * (NotificationChannels); subclasses supply the words and the link. Every
 * stored notification has the same core payload so any bell can render it.
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

    /**
     * Type-specific keys stored alongside the core payload.
     */
    protected function extraData(object $notifiable): array
    {
        return [];
    }
}
