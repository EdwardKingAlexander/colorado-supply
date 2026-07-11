<?php

namespace App\Http\Middleware;

use App\Support\Privacy\ConsentCookie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'admin' => Auth::guard('admin')->user(),
                'guards' => [
                    'admin' => Auth::guard('admin')->check(),
                ],
            ],
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            // Read the header directly rather than the DetectGpcSignal request
            // attribute: share() runs inside this middleware's handle(), so it
            // must not depend on stack ordering.
            'privacy' => [
                'gpc' => $request->headers->get('Sec-GPC') === '1',
                'consent' => ConsentCookie::fromRequest($request),
                'policyVersion' => config('privacy.policy_version'),
                'categories' => config('privacy.categories'),
            ],
        ];
    }
}
