<?php

namespace App\Models;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [

        'order_number',

        'customer_id',

        'quote_id',

        'payment_method',

        'cash_card_name',

        'cash_card_email',

        'cash_card_phone',

        'cash_card_company',

        'contact_name',

        'contact_email',

        'contact_phone',

        'company_name',

        'billing_address',

        'shipping_address',

        'po_number',

        'job_number',

        'notes',

        'internal_notes',

        'subtotal',

        'tax_total',

        'shipping_total',

        'discount_total',

        'grand_total',

        'tax_rate',

        'status',

        'payment_status',

        'fulfillment_status',

        'confirmed_at',

        'paid_at',

        'fulfilled_at',

        'cancelled_at',

        'meta',

        'created_by',

        'updated_by',

        'order_number',

        'portal_user_id',

        'company_id',

    ];

    protected function casts(): array
    {

        return [

            'billing_address' => 'array',

            'shipping_address' => 'array',

            'subtotal' => 'decimal:2',

            'tax_total' => 'decimal:2',

            'shipping_total' => 'decimal:2',

            'discount_total' => 'decimal:2',

            'grand_total' => 'decimal:2',

            'tax_rate' => 'decimal:2',

            'status' => OrderStatus::class,

            'payment_status' => PaymentStatus::class,

            'fulfillment_status' => FulfillmentStatus::class,

            'confirmed_at' => 'datetime',

            'paid_at' => 'datetime',

            'fulfilled_at' => 'datetime',

            'cancelled_at' => 'datetime',

            'meta' => 'array',

        ];

    }

    /**
     * Relationships
     */
    public function customer(): BelongsTo
    {

        return $this->belongsTo(Customer::class);

    }

    public function quote(): BelongsTo
    {

        return $this->belongsTo(Quote::class);

    }

    public function portalUser(): BelongsTo
    {

        return $this->belongsTo(User::class, 'portal_user_id');

    }

    public function items(): HasMany
    {

        return $this->hasMany(OrderItem::class);

    }

    public function payments(): HasMany
    {

        return $this->hasMany(Payment::class);

    }

    public function shipments(): HasMany
    {

        return $this->hasMany(Shipment::class);

    }

    public function creator(): BelongsTo
    {

        return $this->belongsTo(User::class, 'created_by');

    }

    public function updater(): BelongsTo
    {

        return $this->belongsTo(User::class, 'updated_by');

    }

    /**
     * Scopes
     */
    public function scopeByOrderNumber(Builder $query, string $orderNumber): Builder
    {

        return $query->where('order_number', $orderNumber);

    }

    public function scopeUnpaid(Builder $query): Builder
    {

        return $query->where('payment_status', PaymentStatus::Unpaid);

    }

    public function scopePaid(Builder $query): Builder
    {

        return $query->where('payment_status', PaymentStatus::Paid);

    }

    public function scopeConfirmed(Builder $query): Builder
    {

        return $query->where('status', OrderStatus::Confirmed);

    }

    /**
     * Recalculate all totals based on items.
     */
    public function recalcTotals(): void
    {

        // Recalculate subtotal from items

        $this->subtotal = $this->items->sum('line_total');

        // Calculate tax

        $this->tax_total = ($this->subtotal * $this->tax_rate) / 100;

        // Grand total = subtotal + tax + shipping - discount

        $this->grand_total = $this->subtotal + $this->tax_total + $this->shipping_total - $this->discount_total;

        // Ensure no negative totals

        $this->grand_total = max(0, $this->grand_total);

    }

    /**
     * Get the customer email (from customer or cash/card guest).
     */
    public function getCustomerEmailAttribute(): ?string
    {

        if ($this->customer) {

            return $this->customer->email ?? $this->contact_email;

        }

        return $this->cash_card_email ?? $this->contact_email;

    }

    /**
     * Get the customer name (from customer or cash/card guest).
     */
    public function getCustomerNameAttribute(): ?string
    {

        if ($this->customer) {

            return $this->customer->name ?? $this->contact_name;

        }

        return $this->cash_card_name ?? $this->contact_name;

    }

    /**
     * Get the company name.
     */
    public function getCompanyAttribute(): ?string
    {

        if ($this->customer) {

            return $this->customer->company ?? $this->company_name;

        }

        return $this->cash_card_company ?? $this->company_name;

    }

    /**
     * Check if order is paid.
     */
    public function isPaid(): bool
    {

        return $this->payment_status === PaymentStatus::Paid;

    }

    /**
     * Check if order is confirmed.
     */
    public function isConfirmed(): bool
    {

        return $this->status === OrderStatus::Confirmed;

    }

    /**
     * Check if order can be paid.
     */
    public function canBePaid(): bool
    {

        return ! $this->isPaid() && $this->payment_status !== PaymentStatus::Refunded;

    }

    /**
     * Mark order as paid.
     */
    public function markAsPaid(): void
    {

        $this->update([

            'payment_status' => PaymentStatus::Paid,

            'paid_at' => now(),

        ]);

        // Also confirm the order if not already confirmed

        if (! $this->isConfirmed()) {

            $this->markAsConfirmed();

        }

    }

    /**
     * Mark order as confirmed.
     */
    public function markAsConfirmed(): void
    {

        $this->update([

            'status' => OrderStatus::Confirmed,

            'confirmed_at' => now(),

        ]);

    }

    /**
     * Mark order payment as failed.
     */
    public function markPaymentAsFailed(): void
    {

        $this->update([

            'payment_status' => PaymentStatus::Failed,

        ]);

    }

    /**
     * Get grand total in cents for Stripe.
     */
    public function getGrandTotalInCents(): int
    {

        return (int) ($this->grand_total * 100);

    }

    /**
     * Boot method.
     */
    protected static function booted(): void
    {

        static::addGlobalScope(new CompanyScope);

        static::creating(function (Order $order) {

            if (auth()->check()) {

                $order->created_by = auth()->id();

            }

        });

        static::updating(function (Order $order) {

            if (auth()->check()) {

                $order->updated_by = auth()->id();

            }

        });

    }
}
