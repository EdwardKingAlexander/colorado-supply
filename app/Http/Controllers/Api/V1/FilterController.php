<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FilterController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $categoryId = $request->integer('category_id');
        $activeFilters = $request->input('filters', []);

        if ($categoryId <= 0) {
            return response()->json(['data' => []]);
        }

        $productQuery = Product::query()
            ->where('is_active', true)
            ->where('category_id', $categoryId);

        if (is_array($activeFilters) && count($activeFilters) > 0) {
            foreach ($activeFilters as $attributeName => $attributeValue) {
                $this->applyAttributeFilter($productQuery, $attributeName, $attributeValue);
            }
        }

        $productIds = $productQuery->pluck('id');

        if ($productIds->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $attributes = $this->buildAttributeCollection($productIds);
        $productTypeValues = $this->buildProductTypeOptions($categoryId, $activeFilters);

        if ($productTypeValues->isNotEmpty()) {
            $attributes = $attributes
                ->keyBy(fn (array $attribute) => $attribute['name'])
                ->put('Product Type', [
                    'name' => 'Product Type',
                    'type' => 'string',
                    'values' => $productTypeValues,
                ])
                ->values();
        }

        return response()->json([
            'data' => $attributes,
        ]);
    }

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
     * @return Collection<int, array{name: string, type: string, values: \Illuminate\Support\Collection}>
     */
    protected function buildAttributeCollection(Collection $productIds): Collection
    {
        return ProductAttribute::query()
            ->whereIn('product_id', $productIds)
            ->select('name', 'type', 'value')
            ->get()
            ->groupBy('name')
            ->map(function ($group, string $name) {
                $type = $group->first()->type ?? 'string';

                $values = $group
                    ->pluck('value')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();

                if (in_array($type, ['integer', 'float'], true)) {
                    $values = $values
                        ->map(fn ($value) => is_numeric($value) ? (float) $value : $value)
                        ->unique()
                        ->sort()
                        ->values();
                }

                return [
                    'name' => $name,
                    'type' => $type,
                    'values' => $values,
                ];
            })
            ->values();
    }

    protected function buildProductTypeOptions(int $categoryId, array $activeFilters): Collection
    {
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
