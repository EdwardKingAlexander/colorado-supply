<?php

namespace App\Services\Google;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class RecaptchaService
{
    private string $secretKey;
    private float $scoreThreshold;

    public function __construct(?string $secretKey = null, ?float $scoreThreshold = null)
    {
        $this->secretKey = $secretKey ?? (string) config('services.google.recaptcha.secret_key');
        $this->scoreThreshold = $scoreThreshold ?? (float) config('services.google.recaptcha.score_threshold', 0.5);

        if ($this->secretKey === '') {
            throw new RuntimeException('Google reCAPTCHA secret key is not configured.');
        }
    }

    public function verify(string $token, ?string $ip = null, ?string $expectedAction = null): bool
    {
        if ($token === '') {
            return false;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $this->secretKey,
            'response' => $token,
            'remoteip' => $ip,
        ]);

        if (! $response->successful()) {
            return false;
        }

        $payload = $response->json();

        if (($payload['success'] ?? false) !== true) {
            return false;
        }

        if (isset($payload['score']) && $payload['score'] < $this->scoreThreshold) {
            return false;
        }

        if ($expectedAction !== null && ($payload['action'] ?? null) !== $expectedAction) {
            return false;
        }

        return true;
    }
}
