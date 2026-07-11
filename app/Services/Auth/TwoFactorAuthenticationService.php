<?php

namespace App\Services\Auth;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * All second-factor crypto for the customer (web-guard) MFA feature lives here
 * so controllers stay thin and the security-sensitive logic is unit-tested in
 * one place. Covers TOTP (authenticator apps), recovery codes, and email
 * one-time codes.
 */
class TwoFactorAuthenticationService
{
    /** TOTP verification window (± this many 30s periods) to tolerate clock skew. */
    private const TOTP_WINDOW = 1;

    /** Email one-time codes are valid for this many minutes. */
    private const EMAIL_CODE_TTL_MINUTES = 10;

    /** Max email codes a user may request within the decay window. */
    private const EMAIL_CODE_MAX_ATTEMPTS = 5;

    private const EMAIL_CODE_DECAY_SECONDS = 600;

    private const RECOVERY_CODE_COUNT = 8;

    public function __construct(private readonly Google2FA $google2fa) {}

    // ----- Authenticator (TOTP) ------------------------------------------

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Provisioning QR code as an inline SVG string (no image extension needed).
     */
    public function qrCodeSvg(User $user, string $secret): string
    {
        $uri = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret,
        );

        $writer = new Writer(
            new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd)
        );

        return $writer->writeString($uri);
    }

    public function verifyTotp(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code, self::TOTP_WINDOW) !== false;
    }

    // ----- Recovery codes -------------------------------------------------

    /**
     * @return array<int, string>
     */
    public function generateRecoveryCodes(int $count = self::RECOVERY_CODE_COUNT): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::upper(Str::random(5).'-'.Str::random(5)))
            ->all();
    }

    /**
     * Consume a recovery code: remove it from the user's set on match. Returns
     * true if the code was valid (and is now spent).
     */
    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];

        $code = Str::upper(trim($code));

        if (! in_array($code, $codes, true)) {
            return false;
        }

        $user->two_factor_recovery_codes = array_values(array_filter(
            $codes,
            fn ($stored) => $stored !== $code,
        ));
        $user->save();

        return true;
    }

    // ----- Email one-time codes ------------------------------------------

    /**
     * Create a hashed, single-use email code row and return the plaintext code
     * so the caller can deliver it (via notification — wired in the challenge
     * phase). Send-free by design to keep this service unit-testable.
     */
    public function issueEmailCode(User $user, string $purpose): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->mfaCodes()->create([
            'code_hash' => Hash::make($code),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(self::EMAIL_CODE_TTL_MINUTES),
        ]);

        RateLimiter::hit($this->emailRateKey($user), self::EMAIL_CODE_DECAY_SECONDS);

        return $code;
    }

    /**
     * Verify and consume an email code for the given purpose. Hashes are salted,
     * so we check the small set of active rows rather than querying by hash.
     */
    public function verifyEmailCode(User $user, string $code, string $purpose): bool
    {
        $candidates = $user->mfaCodes()
            ->active()
            ->forPurpose($purpose)
            ->latest()
            ->get();

        foreach ($candidates as $candidate) {
            if (Hash::check($code, $candidate->code_hash)) {
                $candidate->update(['consumed_at' => now()]);

                return true;
            }
        }

        return false;
    }

    public function canIssueEmailCode(User $user): bool
    {
        return ! RateLimiter::tooManyAttempts(
            $this->emailRateKey($user),
            self::EMAIL_CODE_MAX_ATTEMPTS,
        );
    }

    private function emailRateKey(User $user): string
    {
        return 'mfa-email-code:'.$user->id;
    }
}
