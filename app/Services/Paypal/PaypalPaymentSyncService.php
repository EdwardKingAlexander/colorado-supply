<?php

namespace App\Services\Paypal;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\OrderPaymentReceived;
use App\Support\OrderNotifier;
use Illuminate\Support\Facades\Log;

/**
 * Applies PayPal webhook event payloads to local Order/Payment records.
 * All lookups bypass CompanyScope since webhook requests are unauthenticated.
 */
class PaypalPaymentSyncService
{
    /**
     * Mark the Payment/Order paid for a completed PayPal capture.
     *
     * @param  array<string, mixed>  $resource  The `resource` object from a `PAYMENT.CAPTURE.COMPLETED` event.
     */
    public function handleCaptureCompleted(array $resource): void
    {
        $payment = $this->resolvePaymentForCapture($resource);

        if (! $payment) {
            Log::warning('PayPal PAYMENT.CAPTURE.COMPLETED: no matching Payment found', [
                'resource' => $resource,
            ]);

            return;
        }

        $this->markPaymentAndOrderPaid($payment, $resource['id'] ?? null);
    }

    /**
     * Mark the given Payment (and its Order) as paid for a completed PayPal
     * capture. Idempotent: a no-op if the Payment is already paid.
     */
    public function markPaymentAndOrderPaid(Payment $payment, ?string $captureId): void
    {
        if ($payment->status === PaymentStatus::Paid) {
            return;
        }

        if ($captureId) {
            $payment->update(['gateway_charge_id' => $captureId]);
        }

        $payment->markAsPaid();

        $order = Order::withoutGlobalScopes()->find($payment->order_id);

        if ($order && ! $order->isPaid()) {
            $order->markAsPaid();
            OrderNotifier::send($order, new OrderPaymentReceived($order));
        }
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function resolvePaymentForCapture(array $resource): ?Payment
    {
        $paypalOrderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;

        if ($paypalOrderId) {
            $payment = Payment::where('gateway', 'paypal')
                ->where('gateway_session_id', $paypalOrderId)
                ->first();

            if ($payment) {
                return $payment;
            }
        }

        $orderId = $resource['custom_id'] ?? null;

        if (! $orderId) {
            return null;
        }

        $order = Order::withoutGlobalScopes()->find($orderId);

        if (! $order) {
            return null;
        }

        return $order->payments()
            ->where('gateway', 'paypal')
            ->where('status', PaymentStatus::Pending)
            ->latest()
            ->first();
    }
}
