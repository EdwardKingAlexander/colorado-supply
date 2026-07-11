<?php

namespace Database\Factories;

use App\Models\PrivacyConsent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PrivacyConsent>
 */
class PrivacyConsentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visitor_uuid' => (string) Str::uuid(),
            'user_id' => null,
            'categories' => ['essential', 'analytics'],
            'gpc_applied' => false,
            'policy_version' => config('privacy.policy_version'),
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'user_agent' => Str::limit(fake()->userAgent(), 255, ''),
        ];
    }

    public function essentialOnly(): static
    {
        return $this->state(fn (): array => ['categories' => ['essential']]);
    }

    public function gpcApplied(): static
    {
        return $this->state(fn (): array => [
            'categories' => ['essential'],
            'gpc_applied' => true,
        ]);
    }
}
