<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'sku',
        'name',
        'description',
        'quantity',
        'unit_price',
        'line_discount',
        'line_total',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'line_discount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate and update line total.
     */
    public function calculateLineTotal(): void
    {
        $this->line_total = ($this->quantity * $this->unit_price) - $this->line_discount;
    }

    protected static function booted(): void
    {
        static::saving(function (OrderItem $item) {
            $item->calculateLineTotal();
        });
    }
}
