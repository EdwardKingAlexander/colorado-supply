<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class, 'product_id');
    }

    public function priceTiers(): HasMany
    {
        return $this->hasMany(ProductPriceTier::class)->orderBy('min_quantity');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Named productRelations to avoid colliding with Eloquent's own $relations.
     */
    public function productRelations(): HasMany
    {
        return $this->hasMany(ProductRelation::class);
    }

    public function substitutes(): BelongsToMany
    {
        return $this->relatedByType(ProductRelation::SUBSTITUTE);
    }

    public function accessories(): BelongsToMany
    {
        return $this->relatedByType(ProductRelation::ACCESSORY);
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->relatedByType(ProductRelation::RELATED);
    }

    protected function relatedByType(string $type): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_relations', 'product_id', 'related_product_id')
            ->wherePivot('type', $type)
            ->withTimestamps();
    }

    public function locationProducts(): HasMany
    {
        return $this->hasMany(LocationProduct::class);
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'location_products')
            ->withPivot(['bin_label', 'on_hand', 'reorder_point', 'max_stock', 'visible'])
            ->withTimestamps();
    }
}
