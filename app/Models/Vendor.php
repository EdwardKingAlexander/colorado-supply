<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'address',
        'slug',
        'description',
        'logo',
        // Compliance fields
        'sam_expiration_date',
        'sam_number',
        'w9_date',
        'tax_id',
        'insurance_expiration_date',
        'insurance_policy_number',
        'cage_code',
        'duns_number',
        'naics_code',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'sam_expiration_date' => 'date',
            'w9_date' => 'date',
            'insurance_expiration_date' => 'date',
        ];
    }

    /**
     * Get the user that owns the vendor.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the products for the vendor.
     */
    public function product(): HasMany
    {
        return $this->hasMany(Product::class, 'product_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(VendorContact::class);
    }

    public function preferredContact(): HasOne
    {
        return $this->hasOne(VendorContact::class)->where('is_preferred', true);
    }

    protected static function booted(): void
    {
        static::creating(function (Vendor $vendor): void {
            if (filled($vendor->slug)) {
                return;
            }

            $baseSlug = Str::slug($vendor->name) ?: 'vendor';
            $slug = $baseSlug;
            $suffix = 2;

            while (static::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix;
                $suffix++;
            }

            $vendor->slug = $slug;
        });
    }
}
