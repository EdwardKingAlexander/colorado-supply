<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    protected function assertBaselineHeaders($response): void
    {
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
    }

    protected function assertEnforcedContentSecurityPolicy($response): void
    {
        $response->assertHeader('Content-Security-Policy');
        $response->assertHeaderMissing('Content-Security-Policy-Report-Only');

        $policy = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("script-src 'self' 'nonce-", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertStringNotContainsString("'unsafe-eval'", $policy);
    }

    protected function assertReportOnlyContentSecurityPolicy($response): void
    {
        $response->assertHeader('Content-Security-Policy-Report-Only');
        $response->assertHeaderMissing('Content-Security-Policy');
    }

    public function test_homepage_has_security_headers(): void
    {
        $response = $this->get('/');

        $this->assertBaselineHeaders($response);
        $this->assertEnforcedContentSecurityPolicy($response);
    }

    public function test_admin_login_page_has_security_headers(): void
    {
        $response = $this->get('/admin/login');

        $this->assertBaselineHeaders($response);
        $this->assertReportOnlyContentSecurityPolicy($response);
    }

    public function test_api_endpoint_has_security_headers(): void
    {
        $response = $this->getJson('/api/v1/does-not-exist');

        $this->assertBaselineHeaders($response);
        $this->assertReportOnlyContentSecurityPolicy($response);
    }

    public function test_public_app_nonce_is_applied_to_inline_scripts(): void
    {
        $response = $this->get('/');

        $policy = $response->headers->get('Content-Security-Policy');
        preg_match("/'nonce-([^']+)'/", $policy, $matches);

        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertStringContainsString('nonce="'.$matches[1].'"', $response->getContent());
    }

    public function test_hsts_is_absent_on_plain_http_request(): void
    {
        $response = $this->get('/');

        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_hsts_is_present_when_request_is_secure(): void
    {
        $response = $this->get('https://localhost/');

        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
