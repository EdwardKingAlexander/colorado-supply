<?php

namespace App\Http\Controllers;

use App\Models\PrivacyConsent;
use App\Support\Privacy\ConsentCookie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ConsentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $knownCategories = array_keys(config('privacy.categories', []));

        $validated = $request->validate([
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['required', 'string', Rule::in($knownCategories)],
            'gpc' => ['sometimes', 'boolean'],
        ]);

        $existingConsent = ConsentCookie::fromRequest($request);
        $visitorUuid = $existingConsent['uuid'] ?? (string) Str::uuid();
        $gpcApplied = $request->attributes->get('gpc') === true
            || $request->headers->get('Sec-GPC') === '1'
            || ($validated['gpc'] ?? false);

        $categories = array_values(array_unique($validated['categories']));

        if ($gpcApplied) {
            $categories = ['essential'];
        } elseif (! in_array('essential', $categories, true)) {
            $categories[] = 'essential';
        }

        $policyVersion = config('privacy.policy_version');

        PrivacyConsent::query()->create([
            'visitor_uuid' => $visitorUuid,
            'user_id' => $request->user()?->id,
            'categories' => $categories,
            'gpc_applied' => $gpcApplied,
            'policy_version' => $policyVersion,
            'ip_hash' => $this->hashIp($request->ip()),
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
        ]);

        $payload = [
            'uuid' => $visitorUuid,
            'categories' => $categories,
            'version' => $policyVersion,
            'timestamp' => now()->toIso8601String(),
        ];

        $cookieConfig = config('privacy.consent_cookie');

        return response()
            ->json(['consent' => $payload, 'gpc_applied' => $gpcApplied])
            ->cookie(
                $cookieConfig['name'],
                json_encode($payload, JSON_THROW_ON_ERROR),
                $cookieConfig['lifetime_days'] * 24 * 60,
                '/',
                null,
                $request->isSecure(),
                false,
                false,
                'lax',
            );
    }

    protected function hashIp(?string $ip): ?string
    {
        if (blank($ip)) {
            return null;
        }

        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
