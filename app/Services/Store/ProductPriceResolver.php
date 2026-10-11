<?php

namespace App\Services\Store;

use App\Models\Admin;
use App\Models\CompanyProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * The price a given viewer pays for a product at a quantity.
 *
 * Precedence: the viewer company's negotiated price (company_products.price)
 * overrides everything; otherwise the highest quantity tier reached; otherwise
 * the base price. GSA and contract prices are returned only to admins and to
 * users of government-buyer companies, and only when the product has them.
 */
class ProductPriceResolver
{
    /**
     * Stateless on purpose: Laravel reuses controller instances (and their
     * injected services), so cached prices could go stale across requests.
     *
     * @param  array<int, ?float>|null  $companyPrices  preloaded via companyPricesFor() when resolving many products
     * @return array{priced: bool, unit_price: ?float, source: ?string, base_price: ?float, list_price: ?float, company_price: ?float, tiers: list<array{min_quantity: int, unit_price: float}>, gsa_price?: float, contract_price?: float}
     */
    public function resolve(Product $product, ?Authenticatable $viewer, int $quantity = 1, ?array $companyPrices = null): array
    {
        $quantity = max(1, $quantity);
        $base = $this->money($product->price);
        $companyPrice = $companyPrices !== null
            ? ($companyPrices[$product->id] ?? null)
            : $this->companyPrice($product, $viewer);

        $tiers = $companyPrice === null
            ? $product->priceTiers
                ->map(fn ($tier) => ['min_quantity' => (int) $tier->min_quantity, 'unit_price' => (float) $tier->unit_price])
                ->values()
                ->all()
            : [];

        [$unitPrice, $source] = match (true) {
            $companyPrice !== null => [$companyPrice, 'company'],
            ($tier = $this->tierFor($tiers, $quantity)) !== null => [$tier['unit_price'], 'tier'],
            $base !== null => [$base, 'base'],
            default => [null, null],
        };

        $pricing = [
            'priced' => $companyPrice !== null || $base !== null || $tiers !== [],
            'unit_price' => $unitPrice,
            'source' => $source,
            'base_price' => $base,
            'list_price' => $this->money($product->list_price),
            'company_price' => $companyPrice,
            'tiers' => $tiers,
        ];

        if ($this->canSeeGovernmentPricing($viewer)) {
            foreach (['gsa_price', 'contract_price'] as $field) {
                if (($value = $this->money($product->{$field})) !== null) {
                    $pricing[$field] = $value;
                }
            }
        }

        return $pricing;
    }

    public function canSeeGovernmentPricing(?Authenticatable $viewer): bool
    {
        return $viewer instanceof Admin
            || ($viewer instanceof User && (bool) $viewer->company?->is_government_buyer);
    }

    /**
     * @param  list<array{min_quantity: int, unit_price: float}>  $tiers  sorted by min_quantity
     */
    private function tierFor(array $tiers, int $quantity): ?array
    {
        $match = null;

        foreach ($tiers as $tier) {
            if ($tier['min_quantity'] <= $quantity) {
                $match = $tier;
            }
        }

        return $match;
    }

    /**
     * The viewer company's prices for many products in one query, to pass to
     * resolve() when pricing a list (e.g. related product cards).
     *
     * @param  iterable<int>  $productIds
     * @return array<int, ?float>|null product id => company price; null when the viewer has no company pricing
     */
    public function companyPricesFor(?Authenticatable $viewer, iterable $productIds): ?array
    {
        if (! $viewer instanceof User || ! $viewer->company_id) {
            return null;
        }

        return CompanyProduct::query()
            ->where('company_id', $viewer->company_id)
            ->whereIn('product_id', collect($productIds)->unique()->values())
            ->pluck('price', 'product_id')
            ->map(fn ($price) => $this->money($price))
            ->all();
    }

    private function companyPrice(Product $product, ?Authenticatable $viewer): ?float
    {
        if (! $viewer instanceof User || ! $viewer->company_id) {
            return null;
        }

        return $this->money(
            CompanyProduct::query()
                ->where('company_id', $viewer->company_id)
                ->where('product_id', $product->id)
                ->value('price')
        );
    }

    private function money(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : round((float) $value, 2);
    }
}
