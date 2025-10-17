<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'method',
        'status',
        'amount',
        'currency',
        'gateway',
        'gateway_payment_intent_id',
        'gateway_charge_id',
        'gateway_session_id',
        'gateway_refund_id',
        'failure_code',
        'failure_message',
        'meta',
        'paid_at',
        'failed_at',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'meta' => 'array',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Mark payment as paid.
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed(?string $code = null, ?string $message = null): void
    {
        $this->update([
            'status' => PaymentStatus::Failed,
            'failed_at' => now(),
            'failure_code' => $code,
            'failure_message' => $message,
        ]);
    }
}
