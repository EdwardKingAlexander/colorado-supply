<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentColor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

         FilamentColor::register([
        'primary'   => '#1d4ed8', // blue-700
        'secondary' => '#4b5563', // gray-600
        'accent'    => '#dc2626', // red-600
        'success'   => '#16a34a', // green-600
        'warning'   => '#d97706', // amber-600
        'danger'    => '#dc2626', // same as accent
    ]);

        
    }
}
