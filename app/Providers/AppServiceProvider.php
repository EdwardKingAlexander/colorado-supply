<?php

namespace App\Providers;

use App\Inertia\Ssr\FastHttpGateway;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Ssr\Gateway;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Gateway::class, FastHttpGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        FilamentColor::register([
            'primary' => '#1d4ed8', // blue-700
            'secondary' => '#4b5563', // gray-600
            'accent' => '#dc2626', // red-600
            'success' => '#16a34a', // green-600
            'warning' => '#d97706', // amber-600
            'danger' => '#dc2626', // same as accent
        ]);

        // Register CRM Observers
        \App\Models\Opportunity::observe(\App\Observers\OpportunityObserver::class);
        \App\Models\Quote::observe(\App\Observers\QuoteObserver::class);

        // Gate::before - Grant all permissions to super_admin
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
                return true;
            }

            return null;
        });

        // Register Policies
        \Illuminate\Support\Facades\Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Customer::class, \App\Policies\CustomerPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Pipeline::class, \App\Policies\PipelinePolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Stage::class, \App\Policies\StagePolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Opportunity::class, \App\Policies\OpportunityPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Quote::class, \App\Policies\QuotePolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\ContractDocument::class, \App\Policies\ContractDocumentPolicy::class);
    }
}
