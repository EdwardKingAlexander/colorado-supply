<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\SmsRecipient;
use App\Models\User;
use App\Notifications\SmsVerificationCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Signing a user or admin up for text alerts: consent, phone verification
 * by code, and stopping (ai/modules/will-call-notifications, phase 6). Shared
 * by the buyer endpoints and the admin My notifications page.
 */
class SmsSubscriptions
{
    /**
     * Record consent for a number and text it a 6-digit code. Changing the
     * number starts over (unverified).
     *
     * @throws ValidationException
     */
    public static function start(User|Admin $owner, ?string $phone, bool $consent, ?string $ip): SmsRecipient
    {
        $e164 = SmsRecipient::normalize($phone);

        if (! $e164) {
            throw ValidationException::withMessages(['phone' => 'Enter a 10-digit US mobile number.']);
        }

        if (! $consent) {
            throw ValidationException::withMessages(['consent' => 'Tick the box to agree to receive texts.']);
        }

        $limiterKey = 'sms-verify:'.$owner->getMorphClass().':'.$owner->getKey();

        if (RateLimiter::tooManyAttempts($limiterKey, (int) config('sms.verification.max_sends_per_hour', 3))) {
            throw ValidationException::withMessages(['phone' => 'Too many codes sent. Try again in an hour.']);
        }

        RateLimiter::hit($limiterKey, 3600);

        $code = (string) random_int(100000, 999999);
        $recipient = $owner->smsRecipient()->firstOrNew();
        $numberChanged = $recipient->phone_e164 !== $e164;

        $recipient->fill([
            'phone_e164' => $e164,
            'consented_at' => now(),
            'consent_ip' => $ip,
            'consent_text_version' => config('sms.consent_version'),
            'verified_at' => $numberChanged ? null : $recipient->verified_at,
            'opted_out_at' => null,
            'verification_code_hash' => Hash::make($code),
            'verification_expires_at' => now()->addMinutes((int) config('sms.verification.ttl_minutes', 10)),
            'verification_attempts' => 0,
        ])->save();

        Notification::route('twilio', $e164)->notify(new SmsVerificationCode($code));

        return $recipient;
    }

    /**
     * @throws ValidationException
     */
    public static function verify(User|Admin $owner, ?string $code): SmsRecipient
    {
        // Fresh read: the owner may be a long-lived instance (Livewire page).
        $recipient = $owner->smsRecipient()->first();
        $fail = fn (string $message) => throw ValidationException::withMessages(['code' => $message]);

        if (! $recipient || ! $recipient->verification_code_hash) {
            $fail('Request a code first.');
        }

        if ($recipient->verification_expires_at?->isPast()) {
            $fail('That code has expired. Request a new one.');
        }

        if ($recipient->verification_attempts >= (int) config('sms.verification.max_attempts', 5)) {
            $fail('Too many tries. Request a new code.');
        }

        if (! Hash::check(preg_replace('/\D/', '', (string) $code), $recipient->verification_code_hash)) {
            $recipient->increment('verification_attempts');
            $fail('That code is not right.');
        }

        $recipient->forceFill([
            'verified_at' => now(),
            'verification_code_hash' => null,
            'verification_expires_at' => null,
            'verification_attempts' => 0,
        ])->save();

        return $recipient;
    }

    public static function stop(User|Admin $owner): void
    {
        $owner->smsRecipient()->delete();
    }

    /**
     * @return array{state: string, phone?: string}
     *                                              state: off | pending (code sent) | on | opted_out
     */
    public static function status(User|Admin $owner): array
    {
        // Fresh read: the owner may be a long-lived instance (Livewire page).
        $recipient = $owner->smsRecipient()->first();

        return match (true) {
            ! $recipient => ['state' => 'off'],
            $recipient->opted_out_at !== null => ['state' => 'opted_out', 'phone' => $recipient->displayPhone()],
            $recipient->canReceive() => ['state' => 'on', 'phone' => $recipient->displayPhone()],
            default => ['state' => 'pending', 'phone' => $recipient->displayPhone()],
        };
    }
}
