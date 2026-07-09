<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPaypalWebhookEvent;
use App\Models\PaypalEvent;
use App\Services\Paypal\PaypalClientFactory;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaypalWebhookController extends Controller
{
    public function __construct(private PaypalClientFactory $client) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        $verification = Http::withToken($this->client->accessToken())
            ->post("{$this->client->baseUrl()}/v1/notifications/verify-webhook-signature", [
                'transmission_id' => $request->header('Paypal-Transmission-Id'),
                'transmission_time' => $request->header('Paypal-Transmission-Time'),
                'cert_url' => $request->header('Paypal-Cert-Url'),
                'auth_algo' => $request->header('Paypal-Auth-Algo'),
                'transmission_sig' => $request->header('Paypal-Transmission-Sig'),
                'webhook_id' => config('services.paypal.webhook_id'),
                'webhook_event' => $payload,
            ]);

        if ($verification->json('verification_status') !== 'SUCCESS') {
            return response()->json(['error' => 'Invalid PayPal webhook signature.'], 400);
        }

        $eventId = $payload['id'] ?? null;

        if (! $eventId) {
            return response()->json(['error' => 'Missing PayPal event id.'], 400);
        }

        if (PaypalEvent::isProcessed($eventId)) {
            return response()->json(['status' => 'already_received']);
        }

        try {
            $paypalEvent = PaypalEvent::create([
                'paypal_event_id' => $eventId,
                'type' => $payload['event_type'] ?? '',
                'payload' => $payload,
            ]);
        } catch (QueryException) {
            return response()->json(['status' => 'already_received']);
        }

        ProcessPaypalWebhookEvent::dispatch($paypalEvent->id);

        return response()->json(['status' => 'received']);
    }
}
