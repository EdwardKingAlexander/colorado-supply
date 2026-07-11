<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Branded replacement for the framework's verification email. Inherits the
 * signed-URL generation from VerifyEmail; only the message copy changes.
 */
class VerifyEmailAddress extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    /**
     * @param  mixed  $notifiable
     */
    protected function buildMailMessage($url): MailMessage
    {
        $expireMinutes = config('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject('Verify your email address — Colorado Supply & Procurement')
            ->greeting('Welcome to Colorado Supply & Procurement')
            ->line('You are receiving this email because this address was used to create a Colorado Supply & Procurement account. To activate your account, please confirm your email address below.')
            ->action('Verify Email Address', $url)
            ->line("This verification link expires in {$expireMinutes} minutes. If it has expired, you can request a new one from the sign-in screen.")
            ->line('If you did not create an account, no further action is required — you can safely ignore this email.')
            ->salutation('— The Colorado Supply & Procurement team');
    }
}
