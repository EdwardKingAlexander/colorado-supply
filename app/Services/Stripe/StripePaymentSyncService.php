<?php

namespace App\Services\Stripe;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\OrderPaymentFailed;
use App\Notifications\OrderPaymentReceived;
use App\Support\OrderNotifier;
use Illuminate\Support\Facades\Log;
use Stripe\Charge;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;

/**
 * Applies Stripe webhook event payloads to local Order/Payment records.
 * All lookups bypass CompanyScope since webhook requests are unauthenticated.
 */
class StripePaymentSyncService
{
    public function handleCheckoutSessionCompleted(Session $session): void
    {
        $payment = Payment::where('gateway', 'stripe')
            ->where('gateway_session_id', $session->id)
            ->first();

        if (! $payment) {
            Log::warning('Stripe checkout.session.completed: no matching Payment found', [
                'gateway_session_id' => $session->id,
            ]);

            return;
        }

        if (isset($session->payment_intent) && is_string($session->payment_intent) && $session->payment_intent !== '') {
            $payment->update(['gateway_payment_intent_id' => $session->payment_intent]);
        }
    }

    public function handlePaymentIntentSucceeded(PaymentIntent $intent): void
    {
        $payment = $this->resolvePaymentForIntent($intent);

        if (! $payment) {
            Log::warning('Stripe payment_intent.succeeded: no matching Payment found', [
                'gateway_payment_intent_id' => $intent->id,
            ]);

            return;
        }

        if ($payment->status === PaymentStatus::Paid) {
            return;
        }

        $payment->update(['gateway_payment_intent_id' => $intent->id]);

        if (isset($intent->latest_charge) && is_string($intent->latest_charge) && $intent->latest_charge !== '') {
            $payment->update(['gateway_charge_id' => $intent->latest_charge]);
        }

        $payment->markAsPaid();

        $order = Order::withoutGlobalScopes()->find($payment->order_id);

        if ($order && ! $order->isPaid()) {
            $order->markAsPaid();
            OrderNotifier::send($order, new OrderPaymentReceived($order));
        }
    }

    public function handlePaymentIntentFailed(PaymentIntent $intent): void
    {
        $payment = $this->resolvePaymentForIntent($intent);

        if (! $payment) {
            Log::warning('Stripe payment_intent.payment_failed: no matching Payment found', [
                'gateway_payment_intent_id' => $intent->id,
            ]);

            return;
        }

        $error = $intent->last_payment_error;

        $payment->markAsFailed($error?->code, $error?->message);

        $order = Order::withoutGlobalScopes()->find($payment->order_id);

        if ($order) {
            $order->markPaymentAsFailed();
            OrderNotifier::send($order, new OrderPaymentFailed($order, $error?->message));
        }
    }

    public function handleChargeRefunded(Charge $charge): void
    {
        $payment = Payment::where('gateway', 'stripe')
            ->where('gateway_charge_id', $charge->id)
            ->first();

        if (! $payment) {
            Log::warning('Stripe charge.refunded: no matching Payment found', [
                'gateway_charge_id' => $charge->id,
            ]);

            return;
        }

        $refund = $charge->refunds?->data[0] ?? null;

        $payment->markAsRefunded($refund?->id);

        $order = Order::withoutGlobalScopes()->find($payment->order_id);

        $order?->update(['payment_status' => PaymentStatus::Refunded]);
    }

    private function resolvePaymentForIntent(PaymentIntent $intent): ?Payment
    {
        $payment = Payment::where('gateway', 'stripe')
            ->where('gateway_payment_intent_id', $intent->id)
            ->first();

        if ($payment) {
            return $payment;
        }

        $orderId = $intent->metadata->order_id ?? null;

        if (! $orderId) {
            return null;
        }

        $order = Order::withoutGlobalScopes()->find($orderId);

        if (! $order) {
            return null;
        }

        return $order->payments()
            ->where('gateway', 'stripe')
            ->where('status', PaymentStatus::Pending)
            ->latest()
            ->first();
    }
}
