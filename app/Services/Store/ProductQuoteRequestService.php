<?php

namespace App\Services\Store;

use App\Models\Admin;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\Admin\QuoteRequestedAlert;
use App\Support\AdminNotifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns a product-page "Request a Quote" into a draft Quote that sales prices
 * in Filament. The unit price is resolved on the server (0 when the product
 * has no price for that quantity), never taken from the browser.
 */
class ProductQuoteRequestService
{
    public function __construct(private ProductPriceResolver $prices) {}

    public function create(User|Admin $requester, Product $product, int $quantity, ?string $note): Quote
    {
        $unitPrice = $this->prices->resolve($product, $requester, $quantity)['unit_price'] ?? 0.0;
        $lineTotal = round($unitPrice * $quantity, 2);
        $portalUser = $requester instanceof User ? $requester : null;

        $quote = DB::transaction(function () use ($product, $quantity, $note, $unitPrice, $lineTotal, $portalUser) {
            $quote = Quote::query()->create([
                'quote_number' => 'Q-'.Carbon::now()->format('Ymd').'-'.Str::upper(Str::random(4)),
                'status' => 'draft',
                'portal_user_id' => $portalUser?->id,
                'company_id' => $portalUser?->company_id,
                'walk_in_label' => 'product-page-quote',
                'walk_in_org' => $portalUser?->company?->name,
                'walk_in_contact_name' => $portalUser?->name,
                'walk_in_email' => $portalUser?->email,
                'currency' => 'USD',
                'tax_rate' => 0,
                'discount_amount' => 0,
                'subtotal' => $lineTotal,
                'tax_total' => 0,
                'grand_total' => $lineTotal,
                'sales_rep_id' => $this->salesRepId($portalUser),
                'notes' => $note,
            ]);

            $quote->items()->create([
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'qty' => $quantity,
                'unit_price' => $unitPrice,
                'line_subtotal' => $lineTotal,
                'line_tax' => 0,
                'line_total' => $lineTotal,
                'uom' => $product->unit_of_measure ?: 'EA',
            ]);

            return $quote;
        });

        AdminNotifier::send(new QuoteRequestedAlert($quote));

        return $quote;
    }

    /**
     * quotes.sales_rep_id is required. Matches the existing web-quote
     * convention (Api\V1\QuoteController): the requesting portal user, else
     * the oldest user.
     */
    private function salesRepId(?User $portalUser): int
    {
        return $portalUser?->id ?? User::query()->oldest('id')->value('id');
    }
}
