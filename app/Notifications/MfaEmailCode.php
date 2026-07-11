<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Delivers a one-time multi-factor authentication code by email, for both
 * enrollment and login challenges. Branded to match the app's transactional mail.
 */
class MfaEmailCode extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $code) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your verification code — Colorado Supply & Procurement')
            ->greeting('Your one-time verification code')
            ->line('Use the code below to complete two-factor authentication. It expires in 10 minutes.')
            ->line('**'.$this->code.'**')
            ->line('If you did not try to sign in or set up two-factor authentication, you can ignore this email — your account is still secure.')
            ->salutation('— The Colorado Supply & Procurement team');
    }
}
