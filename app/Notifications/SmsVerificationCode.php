<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

/**
 * The one-time code that proves a phone number belongs to the person turning
 * on text alerts (ai/modules/will-call-notifications-complete, phase 6). Sent right
 * away (not queued) and only after they consented.
 */
class SmsVerificationCode extends Notification
{
    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return NotificationChannel::Sms->isReady() ? [TwilioChannel::class] : [];
    }

    public function toTwilio(object $notifiable): TwilioSmsMessage
    {
        return (new TwilioSmsMessage)->content(
            "Colorado Supply: your verification code is {$this->code}. It expires in "
            .config('sms.verification.ttl_minutes', 10).' minutes. Reply STOP to opt out.'
        );
    }
}
