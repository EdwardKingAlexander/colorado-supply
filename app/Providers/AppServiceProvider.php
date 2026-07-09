<?php

namespace App\Providers;

use App\Inertia\Ssr\FastHttpGateway;
use App\Models\ContractDocument;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Quote;
use App\Models\Stage;
use App\Models\User;
use App\Observers\OpportunityObserver;
use App\Observers\QuoteObserver;
use App\Policies\ContractDocumentPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\OpportunityPolicy;
use App\Policies\PipelinePolicy;
use App\Policies\QuotePolicy;
use App\Policies\StagePolicy;
use App\Policies\UserPolicy;
use App\Services\Stripe\StripeClientFactory;
use App\Support\ActivitylogCauserResolver;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Ssr\Gateway;
use Spatie\Activitylog\Facades\CauserResolver;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Gateway::class, FastHttpGateway::class);

        $this->app->singleton(StripeClient::class, function ($app) {
            return $app->make(StripeClientFactory::class)->make();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        FilamentColor::register([
            'primary' => '#1d4ed8', // blue-700
            'secondary' => '#4b5563', // gray-600
            'accent' => '#dc2626', // red-600
            'success' => '#16a34a', // green-600
            'warning' => '#d97706', // amber-600
            'danger' => '#dc2626', // same as accent
        ]);

        // Register CRM Observers
        Opportunity::observe(OpportunityObserver::class);
        Quote::observe(QuoteObserver::class);

        CauserResolver::resolveUsing(\Closure::fromCallable(new ActivitylogCauserResolver));

        // Gate::before - Grant all permissions to super_admin
        Gate::before(function ($user, $ability, $arguments = []) {
            // Never blanket-bypass "delete" on User accounts: UserPolicy::delete()
            // enforces safety guards (can't delete yourself, can't delete the last
            // super_admin) that must apply even to super_admins, not just to
            // lower-privileged roles.
            if ($ability === 'delete' && ($arguments[0] ?? null) instanceof User) {
                return null;
            }

            if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
                return true;
            }

            return null;
        });

        // Register Policies
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Pipeline::class, PipelinePolicy::class);
        Gate::policy(Stage::class, StagePolicy::class);
        Gate::policy(Opportunity::class, OpportunityPolicy::class);
        Gate::policy(Quote::class, QuotePolicy::class);
        Gate::policy(ContractDocument::class, ContractDocumentPolicy::class);
    }
}
