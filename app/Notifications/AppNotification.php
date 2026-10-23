<?php

namespace App\Notifications;

use App\Filament\Pages\MyNotifications;
use App\Models\Admin;
use App\Support\SmsQuietHours;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;
use NotificationChannels\WebPush\WebPushMessage;

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

    /**
     * Each channel is its own queued job, so an outage on one (Reverb down,
     * a Twilio error) never blocks the others. A failing channel is retried
     * a few times with backoff, then lands in failed_jobs and the log; the
     * person who triggered it never sees an error.
     */
    public int $tries = 3;

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

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
     * Default email: headline, body lines, one button. Subclasses with richer
     * emails override this or mailLines().
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject($notifiable))
            ->greeting($this->title($notifiable));

        foreach ($this->mailLines($notifiable) as $line) {
            $mail->line($line);
        }

        if ($url = $this->url($notifiable)) {
            $mail->action($this->actionLabel(), $url);
        }

        if ($notifiable instanceof Admin) {
            $mail->line('You get this because store alerts are on for your admin account. Choose which alerts you get: '.MyNotifications::getUrl(panel: 'admin'));
        }

        return $mail;
    }

    protected function subject(object $notifiable): string
    {
        return $this->title($notifiable);
    }

    /**
     * @return list<string>
     */
    protected function mailLines(object $notifiable): array
    {
        return [$this->body($notifiable)];
    }

    protected function actionLabel(): string
    {
        return 'View';
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
     * Lock-screen / desktop notification. The service worker (public/sw.js)
     * opens `data.url` when it is clicked. A per-notification tag keeps
     * separate alerts from replacing each other on the device.
     */
    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title($notifiable))
            ->body($this->body($notifiable))
            ->icon('/apple-touch-icon.png')
            ->badge('/apple-touch-icon.png')
            ->tag($this->type()->value.':'.$this->id)
            ->data(['url' => $this->url($notifiable) ?? ($notifiable instanceof Admin ? '/admin' : '/notifications')])
            ->options(['TTL' => 86400, 'urgency' => 'normal']);
    }

    /**
     * Text message. Plain ASCII (so it stays one 160-character segment
     * instead of 70-character Unicode ones), always branded and ending with
     * the opt-out instruction carriers require.
     */
    public function toTwilio(object $notifiable): TwilioSmsMessage
    {
        $suffix = ' Reply STOP to opt out.';
        $text = 'Colorado Supply: '.$this->smsText($notifiable);
        $text = strtr($text, ['·' => '-', '–' => '-', '—' => '-', '’' => "'", '‘' => "'", '“' => '"', '”' => '"', '×' => 'x', '…' => '...']);
        $text = preg_replace('/[^\x20-\x7E]/', '', $text);

        return (new TwilioSmsMessage)->content(Str::limit($text, 160 - strlen($suffix), '...').$suffix);
    }

    /**
     * The words of the text; the headline by default.
     */
    protected function smsText(object $notifiable): string
    {
        return rtrim($this->title($notifiable), '.').'.';
    }

    /**
     * Hold texts during quiet hours; other channels go immediately.
     *
     * @return array<string, \DateTimeInterface>
     */
    public function withDelay(object $notifiable): array
    {
        $sendAt = SmsQuietHours::sendAt();

        return $sendAt ? [TwilioChannel::class => $sendAt] : [];
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
