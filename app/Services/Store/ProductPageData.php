<?php

namespace App\Services\Store;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Builds the storefront product page payload.
 *
 * Every field and section is optional: anything the product does not have is
 * left out of the payload entirely (no nulls, empty strings or empty lists),
 * so the page can render only what exists (ai/modules/product-detail-page).
 */
class ProductPageData
{
    private const COMPLIANCE_FLAGS = ['berry_compliant', 'taa_compliant', 'made_in_usa', 'gsa_approved', 'qpl_listed'];

    private const WARNING_FLAGS = ['is_hazmat', 'export_controlled'];

    private const MAX_CATEGORY_DEPTH = 10;

    public function __construct(private ProductPriceResolver $prices) {}

    public function build(Product $product, ?Authenticatable $viewer): array
    {
        $product->loadMissing(['vendor:id,name', 'attributes', 'images', 'priceTiers']);

        return $this->compact([
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'url' => $this->url($product),
            'inactive' => $product->is_active ? null : true,
            'sku' => $product->sku,
            'description' => $product->description,
            'vendor' => $product->vendor ? ['id' => $product->vendor->id, 'name' => $product->vendor->name] : null,
            'breadcrumbs' => $this->breadcrumbs($product),
            'unit_of_measure' => $product->unit_of_measure ?: 'EA',
            'minimum_order_quantity' => max(1, (int) ($product->minimum_order_quantity ?: 1)),
            'pricing' => $this->prices->resolve($product, $viewer),
            'identifiers' => $this->pick($product, ['mpn', 'part_number', 'nsn', 'cage_code', 'gtin', 'upc', 'ean']),
            'government' => [
                'standard_type' => $product->standard_type === 'Commercial' ? null : $product->standard_type,
                'mil_spec_number' => $product->mil_spec_number,
            ],
            'classification' => $this->pick($product, ['unspsc', 'psc_fsc', 'naics_code']),
            'compliance' => $this->trueFlags($product, self::COMPLIANCE_FLAGS),
            'warnings' => $this->trueFlags($product, self::WARNING_FLAGS),
            'specs' => [
                'general' => $this->pick($product, ['material', 'finish', 'country_of_origin']),
                'dimensions' => $this->dimensions($product),
                'attributes' => $product->attributes
                    ->map(fn ($attribute) => ['name' => $attribute->name, 'value' => $attribute->value])
                    ->filter(fn (array $attribute) => filled($attribute['name']) && filled($attribute['value']))
                    ->values()
                    ->all(),
                'details' => $product->specifications,
            ],
            'documents' => $this->documents($product),
            'images' => $this->images($product),
            'stock' => $this->stock($product, $viewer),
            'related' => $this->related($product, $viewer),
        ]);
    }

    public function url(Product $product): string
    {
        return route('store.product', [$product, $product->slug], false);
    }

    /**
     * @return list<array{id: int, name: string, slug: ?string}>
     */
    private function breadcrumbs(Product $product): array
    {
        $trail = [];
        $seen = [];
        $category = $product->category_id ? Category::query()->find($product->category_id) : null;

        while ($category && ! isset($seen[$category->id]) && count($trail) < self::MAX_CATEGORY_DEPTH) {
            $seen[$category->id] = true;
            array_unshift($trail, ['id' => $category->id, 'name' => $category->name, 'slug' => $category->slug]);
            $category = $category->parent_id ? Category::query()->find($category->parent_id) : null;
        }

        return $trail;
    }

    private function dimensions(Product $product): array
    {
        return collect(['length_mm', 'width_mm', 'height_mm', 'weight_g'])
            ->mapWithKeys(fn (string $field) => [$field => $product->{$field} ? (int) $product->{$field} : null])
            ->all();
    }

    /**
     * @return list<array{type: string, label: string, url: string}>
     */
    private function documents(Product $product): array
    {
        return collect([
            ['type' => 'datasheet', 'label' => 'Datasheet', 'url' => $product->datasheet_url],
            ['type' => 'sds', 'label' => 'Safety Data Sheet (SDS)', 'url' => $product->msds_url],
        ])->filter(fn (array $document) => filled($document['url']))->values()->all();
    }

    /**
     * Primary image first, then the ordered gallery.
     *
     * @return list<array{url: string, alt: string}>
     */
    private function images(Product $product): array
    {
        $images = [];

        if (filled($product->image)) {
            $images[] = ['url' => $this->imageUrl($product->image), 'alt' => $product->name];
        }

        foreach ($product->images as $image) {
            $images[] = ['url' => $this->imageUrl($image->path), 'alt' => filled($image->alt) ? $image->alt : $product->name];
        }

        return $images;
    }

    private function imageUrl(string $path): string
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
            ? $path
            : '/storage/'.ltrim($path, '/');
    }

    /**
     * Per-location stock for the locations this viewer can see (hidden
     * locations skipped); falls back to the product-level stock count.
     */
    private function stock(Product $product, ?Authenticatable $viewer): array
    {
        $rows = $this->visibleLocationStock($product, $viewer);

        if ($rows->isNotEmpty()) {
            $onHand = (int) $rows->sum('on_hand');
            $locations = $rows->values()->all();
        } else {
            $onHand = $product->stock === null ? null : (int) $product->stock;
            $locations = [];
        }

        return [
            'in_stock' => $onHand === null ? null : $onHand > 0,
            'on_hand' => $onHand,
            'locations' => $locations,
            'lead_time_days' => $product->lead_time_days ? (int) $product->lead_time_days : null,
        ];
    }

    /**
     * @return Collection<int, array{id: int, name: string, on_hand: int}>
     */
    private function visibleLocationStock(Product $product, ?Authenticatable $viewer): Collection
    {
        if ($viewer instanceof User) {
            if (! $viewer->company_id) {
                return collect();
            }

            $locationIds = Location::query()->where('company_id', $viewer->company_id)->pluck('id');
        } elseif ($viewer instanceof Admin) {
            $locationIds = null;
        } else {
            return collect();
        }

        return $product->locationProducts()
            ->where('visible', true)
            ->when($locationIds !== null, fn ($query) => $query->whereIn('location_id', $locationIds))
            ->whereHas('location')
            ->with('location:id,name')
            ->orderBy('location_id')
            ->get()
            ->map(fn ($row) => ['id' => $row->location->id, 'name' => $row->location->name, 'on_hand' => (int) $row->on_hand]);
    }

    /**
     * Active related products, grouped by relation type, as compact cards.
     */
    private function related(Product $product, ?Authenticatable $viewer): array
    {
        $groups = [
            'substitutes' => $product->substitutes(),
            'accessories' => $product->accessories(),
            'related' => $product->relatedProducts(),
        ];

        $groups = collect($groups)->map(fn ($relation) => $relation
            ->where('products.is_active', true)
            ->with('priceTiers')
            ->orderBy('products.name')
            ->get());

        // One company-price query for every card instead of one per card.
        $companyPrices = $this->prices->companyPricesFor($viewer, $groups->flatten()->pluck('id'));

        return $groups
            ->map(fn ($products) => $products
                ->map(fn (Product $related) => $this->compact([
                    'id' => $related->id,
                    'name' => $related->name,
                    'sku' => $related->sku,
                    'url' => $this->url($related),
                    'image' => filled($related->image) ? $this->imageUrl($related->image) : null,
                    'unit_of_measure' => $related->unit_of_measure ?: 'EA',
                    'pricing' => $this->prices->resolve($related, $viewer, 1, $companyPrices),
                ]))
                ->all())
            ->all();
    }

    private function pick(Product $product, array $fields): array
    {
        return collect($fields)->mapWithKeys(fn (string $field) => [$field => $product->{$field}])->all();
    }

    /**
     * @return list<string>
     */
    private function trueFlags(Product $product, array $flags): array
    {
        return array_values(array_filter($flags, fn (string $flag) => (bool) $product->{$flag}));
    }

    /**
     * Recursively drop nulls, blank strings and empty arrays. false and 0 stay.
     */
    private function compact(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->compact($value);
            } elseif (is_string($value)) {
                $value = trim($value);
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $result[$key] = $value;
        }

        return array_is_list($data) ? array_values($result) : $result;
    }
}
