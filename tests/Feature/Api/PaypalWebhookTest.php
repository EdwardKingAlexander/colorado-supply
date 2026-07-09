<?php

namespace Tests\Feature\Api;

use App\Jobs\ProcessPaypalWebhookEvent;
use App\Models\PaypalEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\Support\Paypal\FakesPaypalHttp;
use Tests\TestCase;

class PaypalWebhookTest extends TestCase
{
    use FakesPaypalHttp;
    use RefreshDatabase;

    private function eventPayload(string $type, array $resource, string $id = 'WH-EVT-123'): array
    {
        return [
            'id' => $id,
            'event_type' => $type,
            'resource' => $resource,
        ];
    }

    public function test_valid_signature_creates_paypal_event_and_dispatches_job(): void
    {
        $this->fakePaypal();
        Bus::fake();

        $payload = $this->eventPayload('PAYMENT.CAPTURE.COMPLETED', [
            'id' => 'CAPTURE123',
        ]);

        $response = $this->postJson('/api/v1/paypal/webhook', $payload);

        $response->assertSuccessful();

        $this->assertDatabaseHas('paypal_events', [
            'paypal_event_id' => 'WH-EVT-123',
            'type' => 'PAYMENT.CAPTURE.COMPLETED',
        ]);

        $paypalEvent = PaypalEvent::where('paypal_event_id', 'WH-EVT-123')->first();

        Bus::assertDispatched(
            ProcessPaypalWebhookEvent::class,
            fn (ProcessPaypalWebhookEvent $job) => $job->paypalEventId === $paypalEvent->id,
        );
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $this->fakePaypal([
            '*/v1/notifications/verify-webhook-signature' => Http::response([
                'verification_status' => 'FAILURE',
            ], 200),
        ]);
        Bus::fake();

        $payload = $this->eventPayload('PAYMENT.CAPTURE.COMPLETED', [
            'id' => 'CAPTURE123',
        ]);

        $response = $this->postJson('/api/v1/paypal/webhook', $payload);

        $response->assertStatus(400);

        $this->assertDatabaseMissing('paypal_events', [
            'paypal_event_id' => 'WH-EVT-123',
        ]);

        Bus::assertNotDispatched(ProcessPaypalWebhookEvent::class);
    }

    public function test_duplicate_event_id_is_not_reprocessed(): void
    {
        $this->fakePaypal();
        Bus::fake();

        $payload = $this->eventPayload('PAYMENT.CAPTURE.COMPLETED', [
            'id' => 'CAPTURE123',
        ]);

        $this->postJson('/api/v1/paypal/webhook', $payload)->assertSuccessful();

        $response = $this->postJson('/api/v1/paypal/webhook', $payload);

        $response->assertSuccessful();

        $this->assertDatabaseCount('paypal_events', 1);

        Bus::assertDispatchedTimes(ProcessPaypalWebhookEvent::class, 1);
    }
}
