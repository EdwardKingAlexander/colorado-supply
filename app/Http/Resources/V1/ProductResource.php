<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @mixin \App\Models\Product
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'mpn' => $this->mpn,
            'gtin' => $this->gtin,
            'price' => $this->price !== null ? (float) $this->price : null,
            'list_price' => $this->list_price !== null ? (float) $this->list_price : null,
            'description' => $this->description,
            'image' => $this->image,
            'category_id' => $this->category_id,
            'unit' => $this->resolveUnit(),
            'specifications' => $this->resolvedSpecifications(),
            'is_active' => (bool) $this->is_active,

            // Inventory information
            'stock' => $this->stock !== null ? (int) $this->stock : null,
            'in_stock' => $this->stock !== null && $this->stock > 0,
            'lead_time_days' => $this->lead_time_days !== null ? (int) $this->lead_time_days : null,

            // Physical dimensions
            'dimensions' => [
                'weight_g' => $this->weight_g !== null ? (float) $this->weight_g : null,
                'length_mm' => $this->length_mm !== null ? (float) $this->length_mm : null,
                'width_mm' => $this->width_mm !== null ? (float) $this->width_mm : null,
                'height_mm' => $this->height_mm !== null ? (float) $this->height_mm : null,
            ],

            // Classification
            'unspsc' => $this->unspsc,
            'country_of_origin' => $this->country_of_origin,

            'category' => $this->whenLoaded('category', function () {
                return CategoryResource::make($this->category);
            }),

            'vendor' => $this->whenLoaded('vendor', function () {
                return [
                    'id' => $this->vendor->id,
                    'name' => $this->vendor->name,
                    'slug' => $this->vendor->slug,
                ];
            }),
        ];
    }

    protected function resolveUnit(): ?string
    {
        $meta = $this->meta;

        if (empty($meta)) {
            return null;
        }

        $decoded = is_array($meta) ? $meta : (json_decode((string) $meta, true) ?: []);

        return is_array($decoded) ? ($decoded['unit'] ?? null) : null;
    }

    /**
     * @return list<array{name: string, type: string, value: ?string}>
     */
    protected function resolvedSpecifications(): array
    {
        if (! $this->relationLoaded('attributes')) {
            return [];
        }

        /** @var Collection<int, \App\Models\ProductAttribute>|null $attributes */
        $attributes = $this->resource->getRelationValue('attributes');

        if ($attributes === null) {
            return [];
        }

        return $attributes
            ->map(fn ($attribute) => [
                'name' => $attribute->name,
                'type' => $attribute->type,
                'value' => $attribute->value,
            ])
            ->values()
            ->all();
    }
}
