<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessStripeWebhookEvent;
use App\Models\StripeEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return response()->json(['error' => 'Invalid Stripe webhook payload or signature.'], 400);
        }

        if (StripeEvent::isProcessed($event->id)) {
            return response()->json(['status' => 'already_received']);
        }

        try {
            $stripeEvent = StripeEvent::create([
                'stripe_event_id' => $event->id,
                'type' => $event->type,
                'payload' => $event->toArray(),
            ]);
        } catch (QueryException) {
            return response()->json(['status' => 'already_received']);
        }

        ProcessStripeWebhookEvent::dispatch($stripeEvent->id);

        return response()->json(['status' => 'received']);
    }
}
