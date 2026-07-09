<?php

namespace App\Jobs;

use App\Models\PaypalEvent;
use App\Services\Paypal\PaypalPaymentSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPaypalWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $paypalEventId) {}

    public function handle(PaypalPaymentSyncService $paymentSync): void
    {
        $paypalEvent = PaypalEvent::find($this->paypalEventId);

        if (! $paypalEvent || $paypalEvent->processed_at !== null) {
            return;
        }

        $resource = $paypalEvent->payload['resource'] ?? [];

        match ($paypalEvent->type) {
            'PAYMENT.CAPTURE.COMPLETED' => $paymentSync->handleCaptureCompleted($resource),
            default => Log::info('Unhandled PayPal webhook event type', [
                'paypal_event_id' => $paypalEvent->paypal_event_id,
                'type' => $paypalEvent->type,
            ]),
        };

        $paypalEvent->markAsProcessed();
    }
}
