<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quote_number',
        'status',
        'customer_id',
        'portal_user_id',
        'walk_in_label',
        'walk_in_org',
        'walk_in_contact_name',
        'walk_in_email',
        'walk_in_phone',
        'walk_in_billing_json',
        'walk_in_shipping_json',
        'currency',
        'tax_rate',
        'discount_amount',
        'subtotal',
        'tax_total',
        'grand_total',
        'sales_rep_id',
        'notes',
        'company_id',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }

    protected function casts(): array
    {
        return [
            'walk_in_billing_json' => 'array',
            'walk_in_shipping_json' => 'array',
            'tax_rate' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'portal_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function isWalkIn(): bool
    {
        return $this->customer_id === null;
    }

    public function getCustomerDisplayNameAttribute(): string
    {
        if ($this->isWalkIn()) {
            return $this->walk_in_label.($this->walk_in_org ? ' - '.$this->walk_in_org : '');
        }

        return $this->customer->name ?? 'Unknown';
    }
}
