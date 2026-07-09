<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HttpsEnforcementTest extends TestCase
{
    public function test_generated_urls_use_https_in_production(): void
    {
        $this->app['env'] = 'production';
        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', url('/'));
    }

    public function test_generated_urls_do_not_force_https_in_testing(): void
    {
        URL::forceScheme(null);

        $this->assertStringStartsWith('http://', url('/'));
    }
}
