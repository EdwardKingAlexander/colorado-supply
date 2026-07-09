<?php

namespace Tests\Feature;

use App\Mail\RepairRequestAutoReply;
use App\Mail\RepairRequestSubmitted;
use App\Models\RepairRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RepairServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSuccessfulRecaptcha(): void
    {
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
                'action' => 'repair_request_form',
            ]),
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'equipment_type' => 'Servo Motor',
            'model_number' => 'XYZ-99',
            'issue_description' => 'Not spinning up under load.',
            'captcha_token' => 'test-token',
        ], $overrides);
    }

    public function test_repair_services_page_renders_without_auth(): void
    {
        $this->get('/repair-services')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('RepairServices/Index'));
    }

    public function test_valid_submission_creates_request_and_sends_mail(): void
    {
        Mail::fake();
        $this->fakeSuccessfulRecaptcha();

        $response = $this->postJson('/repair-services', $this->validPayload());

        $response->assertCreated();

        $this->assertDatabaseHas('repair_requests', [
            'email' => 'jane@example.com',
            'equipment_type' => 'Servo Motor',
            'model_number' => 'XYZ-99',
        ]);

        Mail::assertSent(RepairRequestSubmitted::class);
        Mail::assertSent(RepairRequestAutoReply::class);
    }

    public function test_optional_fields_can_be_omitted(): void
    {
        Mail::fake();
        $this->fakeSuccessfulRecaptcha();

        $response = $this->postJson('/repair-services', $this->validPayload());

        $response->assertCreated();
        $this->assertSame(1, RepairRequest::count());
    }

    public function test_missing_required_fields_returns_validation_errors(): void
    {
        Mail::fake();
        $this->fakeSuccessfulRecaptcha();

        $response = $this->postJson('/repair-services', [
            'phone' => '719-555-0100',
            'captcha_token' => 'test-token',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name', 'email', 'equipment_type', 'model_number', 'issue_description',
        ]);

        $this->assertSame(0, RepairRequest::count());
        Mail::assertNothingSent();
    }

    public function test_honeypot_field_silently_drops_submission(): void
    {
        Mail::fake();

        $response = $this->postJson('/repair-services', $this->validPayload([
            'website' => 'http://spam.example',
            'email' => 'bot@example.com',
        ]));

        $response->assertCreated();
        $this->assertSame(0, RepairRequest::count());
        Mail::assertNothingSent();
    }

    public function test_failed_recaptcha_returns_validation_error(): void
    {
        Mail::fake();

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => false,
            ]),
        ]);

        $response = $this->postJson('/repair-services', $this->validPayload());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['captcha_token']);

        $this->assertSame(0, RepairRequest::count());
        Mail::assertNothingSent();
    }
}
