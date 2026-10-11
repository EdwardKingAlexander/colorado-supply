<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Directed link from a product to a substitute, accessory or related product.
 */
class ProductRelation extends Model
{
    public const SUBSTITUTE = 'substitute';

    public const ACCESSORY = 'accessory';

    public const RELATED = 'related';

    public const TYPES = [
        self::SUBSTITUTE => 'Substitute',
        self::ACCESSORY => 'Accessory',
        self::RELATED => 'Related',
    ];

    protected $fillable = ['product_id', 'related_product_id', 'type'];

    protected static function booted(): void
    {
        static::saving(function (ProductRelation $relation) {
            if ((int) $relation->product_id === (int) $relation->related_product_id) {
                throw new InvalidArgumentException('A product cannot be related to itself.');
            }

            if (! array_key_exists($relation->type, self::TYPES)) {
                throw new InvalidArgumentException("Unknown product relation type [{$relation->type}].");
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function relatedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'related_product_id');
    }
}
