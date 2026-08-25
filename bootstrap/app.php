<?php

use App\Console\Commands\EnsureEdwardAdminUser;
use App\Http\Middleware\AuthenticateWebOrAdmin;
use App\Http\Middleware\DetectGpcSignal;
use App\Http\Middleware\EnsureEmailIsVerifiedWhenEnabled;
use App\Http\Middleware\EnsureMfaSatisfied;
use App\Http\Middleware\EnsureStoreEnabled;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ScopeToCompany;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->withoutMiddleware(PreventRequestForgery::class)
                ->group(base_path('routes/ai.php'));
        },
    )
    ->withCommands([
        EnsureEdwardAdminUser::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SecurityHeaders::class);

        $middleware->statefulApi();

        // Privacy compliance: GPC opt-out detection runs before anything that
        // consumes the flag; the consent cookie stays unencrypted so the
        // client-side analytics bootstrap can read the same value (it holds
        // no secrets — see config/privacy.php).
        $middleware->web(prepend: [DetectGpcSignal::class]);
        $middleware->encryptCookies(except: ['cs_privacy_consent']);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            EnsureMfaSatisfied::class,
        ]);

        // Web middleware stack
        $middleware->web([
            HandleInertiaRequests::class,
            ScopeToCompany::class,
        ]);

        // Middleware aliases
        $middleware->alias([
            'auth.web_or_admin' => AuthenticateWebOrAdmin::class,
            'store.enabled' => EnsureStoreEnabled::class,
            'scope.company' => ScopeToCompany::class,
            'verified.enabled' => EnsureEmailIsVerifiedWhenEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })->create();
