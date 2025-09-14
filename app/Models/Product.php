<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{

    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'stock',
        'is_active',
    ];

    /**
     * Get the vendor that owns the product.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }


}
