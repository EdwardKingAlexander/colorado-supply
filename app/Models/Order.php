<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'quote_id',
        'customer_id',
        'payment_method',
        'po_number',
        'job_number',
        'order_total',
        'status',
        'walk_in_label',
        'walk_in_org',
        'walk_in_contact_name',
        'walk_in_email',
        'walk_in_phone',
        'walk_in_billing_json',
        'walk_in_shipping_json',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'walk_in_billing_json' => 'array',
            'walk_in_shipping_json' => 'array',
            'order_total' => 'decimal:2',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
