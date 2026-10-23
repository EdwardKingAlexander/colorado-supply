<?php

namespace App\Models\Concerns;

use App\Models\SmsRecipient;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Text message routing for users and admins (ai/modules/will-call-notifications-complete,
 * phase 6). The Twilio channel gets a number only when it may be texted.
 */
trait ReceivesTextMessages
{
    public function smsRecipient(): MorphOne
    {
        return $this->morphOne(SmsRecipient::class, 'owner');
    }

    public function routeNotificationForTwilio(): ?string
    {
        $recipient = $this->smsRecipient;

        return $recipient?->canReceive() ? $recipient->phone_e164 : null;
    }
}
