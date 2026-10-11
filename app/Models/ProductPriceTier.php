<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A quantity price break: buying min_quantity or more costs unit_price each.
 */
class ProductPriceTier extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'min_quantity', 'unit_price'];

    protected function casts(): array
    {
        return [
            'min_quantity' => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
