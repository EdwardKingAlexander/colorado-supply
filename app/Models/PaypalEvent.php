<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaypalEvent extends Model
{
    protected $fillable = [
        'paypal_event_id',
        'type',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Check if event has already been processed.
     */
    public static function isProcessed(string $eventId): bool
    {
        return static::where('paypal_event_id', $eventId)->exists();
    }

    /**
     * Mark event as processed.
     */
    public function markAsProcessed(): void
    {
        $this->update(['processed_at' => now()]);
    }
}
