<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A user's or admin's phone for text alerts (ai/modules/will-call-notifications,
 * phase 6). Texts go out only when the number is verified, consented to and
 * not opted out (STOP).
 */
class SmsRecipient extends Model
{
    protected $fillable = [
        'phone_e164', 'consented_at', 'consent_ip', 'consent_text_version', 'verified_at', 'opted_out_at',
        'verification_code_hash', 'verification_expires_at', 'verification_attempts',
    ];

    protected $hidden = ['verification_code_hash'];

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'verified_at' => 'datetime',
            'opted_out_at' => 'datetime',
            'verification_expires_at' => 'datetime',
            'verification_attempts' => 'integer',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function canReceive(): bool
    {
        return $this->verified_at !== null && $this->consented_at !== null && $this->opted_out_at === null;
    }

    /**
     * US numbers only: 10 digits, or 11 starting with 1, in any punctuation.
     * Returns +1XXXXXXXXXX or null.
     */
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            $digits = substr($digits, 1);
        }

        // NANP: area code and exchange can't start with 0 or 1.
        return preg_match('/^[2-9]\d{2}[2-9]\d{6}$/', $digits) ? '+1'.$digits : null;
    }

    /** "(303) 555-0100" style, for showing back to people. */
    public function displayPhone(): string
    {
        $digits = substr($this->phone_e164, 2);

        return sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6));
    }
}
