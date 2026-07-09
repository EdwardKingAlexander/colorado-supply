<?php

namespace App\Services\Stripe;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\Refund;
use Stripe\StripeClient;

class StripeRefundService
{
    public function __construct(private StripeClient $stripe) {}

    /**
     * Issue a refund for a paid Stripe payment.
     *
     * @param  int|null  $amountCents  Amount to refund in cents. Null refunds the full remaining amount.
     * @param  string|null  $reason  One of 'duplicate', 'fraudulent', 'requested_by_customer'.
     *
     * @throws RuntimeException if the payment cannot be refunded.
     */
    public function refund(Payment $payment, ?int $amountCents = null, ?string $reason = null): Refund
    {
        if ($payment->gateway !== 'stripe') {
            throw new RuntimeException('Only Stripe payments can be refunded through this service.');
        }

        if ($payment->status !== PaymentStatus::Paid) {
            throw new RuntimeException('Only paid payments can be refunded.');
        }

        if (! $payment->gateway_charge_id) {
            throw new RuntimeException('Payment has no associated Stripe charge to refund.');
        }

        $params = ['charge' => $payment->gateway_charge_id];

        if ($amountCents !== null) {
            $params['amount'] = $amountCents;
        }

        if ($reason !== null) {
            $params['reason'] = $reason;
        }

        try {
            $refund = $this->stripe->refunds->create($params);
        } catch (ApiErrorException $e) {
            Log::error('Stripe refund failed', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'gateway_charge_id' => $payment->gateway_charge_id,
                'stripe_error_code' => $e->getStripeCode(),
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to process refund. Please try again shortly.', previous: $e);
        }

        $paymentAmountCents = (int) round(((float) $payment->amount) * 100);
        $isFullRefund = $amountCents === null || $amountCents >= $paymentAmountCents;

        if ($isFullRefund) {
            $payment->markAsRefunded($refund->id);

            return $refund;
        }

        $existingRefunds = $payment->meta['refunds'] ?? [];
        $existingRefunds[] = [
            'id' => $refund->id,
            'amount' => $amountCents,
            'reason' => $reason,
            'created_at' => now()->toIso8601String(),
        ];

        $payment->update([
            'gateway_refund_id' => $refund->id,
            'meta' => array_merge($payment->meta ?? [], ['refunds' => $existingRefunds]),
        ]);

        return $refund;
    }
}
