<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'tracking_number',
        'carrier',
        'service',
        'status',
        'shipping_address',
        'meta',
        'shipped_at',
        'delivered_at',
        'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'meta' => 'array',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
