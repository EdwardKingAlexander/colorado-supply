<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = $this->baseQuery($request)
            ->paginate(20)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function show(string $slug): ProductResource
    {
        $product = Product::query()
            ->with([
                'category:id,name,slug,parent_id',
                'attributes:id,product_id,name,type,value',
                'vendor:id,name,slug',
            ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return ProductResource::make($product);
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        $searchTerm = trim($request->string('query')->toString());

        $products = $this->baseQuery($request)
            ->when($searchTerm !== '', function (Builder $query) use ($searchTerm): void {
                $query->where(function (Builder $inner) use ($searchTerm): void {
                    $inner->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('description', 'like', "%{$searchTerm}%")
                        ->orWhere('sku', 'like', "%{$searchTerm}%")
                        ->orWhere('mpn', 'like', "%{$searchTerm}%");
                });
            })
            ->paginate(20)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function filters(Request $request): array
    {
        $categoryId = $request->integer('category_id');
        $activeFilters = $request->input('filters', []);

        // Build query for products matching category and active filters
        $query = Product::query()
            ->where('is_active', true)
            ->when($categoryId > 0, fn (Builder $q) => $q->where('category_id', $categoryId));

        // Apply active filters to narrow down products
        if (is_array($activeFilters) && count($activeFilters) > 0) {
            foreach ($activeFilters as $attributeName => $attributeValue) {
                $this->applyAttributeFilter($query, $attributeName, $attributeValue);
            }
        }

        // Get product IDs that match all criteria
        $productIds = $query->pluck('id');

        // Get all unique attributes and their values for these filtered products
        $attributes = ProductAttribute::query()
            ->whereIn('product_id', $productIds)
            ->select('name', 'type', 'value')
            ->get()
            ->groupBy('name')
            ->map(function ($group, $name) {
                $type = $group->first()->type;
                $values = $group->pluck('value')->filter()->unique()->sort()->values();

                return [
                    'name' => $name,
                    'type' => $type,
                    'values' => $values,
                ];
            })
            ->values();

        $productTypeValues = $this->buildProductTypeOptions($categoryId, $activeFilters);

        if ($productTypeValues->isNotEmpty()) {
            $attributes = $attributes
                ->keyBy(fn (array $attribute): string => $attribute['name'])
                ->put('Product Type', [
                    'name' => 'Product Type',
                    'type' => 'string',
                    'values' => $productTypeValues,
                ])
                ->values();
        }

        return [
            'data' => $attributes,
        ];
    }

    protected function baseQuery(Request $request): Builder
    {
        $categoryId = $request->integer('category_id');
        $filters = $request->input('filters', []);

        $query = Product::query()
            ->with([
                'category:id,name,slug,parent_id',
                'attributes:id,product_id,name,type,value',
                'vendor:id,name,slug',
            ])
            ->where('is_active', true)
            ->when($categoryId > 0, function (Builder $query) use ($categoryId): void {
                $query->where('category_id', $categoryId);
            });

        // Apply attribute-based filters
        if (is_array($filters) && count($filters) > 0) {
            foreach ($filters as $attributeName => $attributeValue) {
                $this->applyAttributeFilter($query, $attributeName, $attributeValue);
            }
        }

        return $query->orderBy('name');
    }

    /**
     * Apply attribute-based filtering, supporting single and multi-value selections.
     */
    protected function applyAttributeFilter(Builder $query, string $attributeName, mixed $attributeValue): void
    {
        $values = is_array($attributeValue) ? $attributeValue : [$attributeValue];
        $values = array_values(array_filter($values, static fn ($value) => $value !== null && $value !== ''));

        if (count($values) === 0) {
            return;
        }

        $query->whereHas('attributes', function (Builder $q) use ($attributeName, $values): void {
            $q->where('name', $attributeName)
                ->whereIn('value', $values);
        });
    }

    /**
     * Build the full Product Type option list so selections remain available.
     */
    protected function buildProductTypeOptions(int $categoryId, array $activeFilters = []): Collection
    {
        if ($categoryId <= 0) {
            return collect();
        }

        $query = Product::query()
            ->where('is_active', true)
            ->where('category_id', $categoryId);

        foreach ($activeFilters as $attributeName => $attributeValue) {
            if ($attributeName === 'Product Type') {
                continue;
            }

            $this->applyAttributeFilter($query, $attributeName, $attributeValue);
        }

        $productIds = $query->pluck('id');

        if ($productIds->isEmpty()) {
            return collect();
        }

        return ProductAttribute::query()
            ->whereIn('product_id', $productIds)
            ->where('name', 'Product Type')
            ->pluck('value')
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }
}
