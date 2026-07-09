<?php

namespace App\Jobs;

use App\Models\StripeEvent;
use App\Services\Stripe\StripePaymentSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stripe\Charge;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;

class ProcessStripeWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $stripeEventId) {}

    public function handle(StripePaymentSyncService $paymentSync): void
    {
        $stripeEvent = StripeEvent::find($this->stripeEventId);

        if (! $stripeEvent || $stripeEvent->processed_at !== null) {
            return;
        }

        $object = $stripeEvent->payload['data']['object'] ?? [];

        match ($stripeEvent->type) {
            'checkout.session.completed' => $paymentSync->handleCheckoutSessionCompleted(Session::constructFrom($object)),
            'payment_intent.succeeded' => $paymentSync->handlePaymentIntentSucceeded(PaymentIntent::constructFrom($object)),
            'payment_intent.payment_failed' => $paymentSync->handlePaymentIntentFailed(PaymentIntent::constructFrom($object)),
            'charge.refunded' => $paymentSync->handleChargeRefunded(Charge::constructFrom($object)),
            default => Log::info('Unhandled Stripe webhook event type', [
                'stripe_event_id' => $stripeEvent->stripe_event_id,
                'type' => $stripeEvent->type,
            ]),
        };

        $stripeEvent->markAsProcessed();
    }
}
