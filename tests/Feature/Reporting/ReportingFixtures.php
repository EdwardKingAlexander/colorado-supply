<?php

namespace Tests\Feature\Reporting;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Shipment;
use App\Models\User;
use App\Reporting\Definition\ReportDefinitionValidator;
use App\Reporting\ReportResult;
use App\Reporting\ReportRunner;
use Illuminate\Support\Str;

/**
 * Builds tenants and their orders/quotes for report engine tests.
 */
final class ReportingFixtures
{
    /**
     * A company with a user, a parent location and one sublocation. Pass
     * $withCompany = false for a portal user who has no company.
     *
     * @return array{user: User, company: ?Company, location: ?Location, sublocation: ?Location}
     */
    public static function tenant(string $name, bool $withCompany = true): array
    {
        if (! $withCompany) {
            return ['user' => User::factory()->create(['company_id' => null]), 'company' => null, 'location' => null, 'sublocation' => null];
        }

        $company = Company::create(['name' => $name, 'slug' => Str::slug($name).'-'.Str::lower(Str::random(4))]);
        $location = Location::create(['company_id' => $company->id, 'name' => "{$name} HQ", 'slug' => Str::slug("{$name} hq")]);
        $sublocation = Location::create(['company_id' => $company->id, 'parent_id' => $location->id, 'name' => "{$name} Annex", 'slug' => Str::slug("{$name} annex")]);

        return [
            'user' => User::factory()->create(['company_id' => $company->id]),
            'company' => $company,
            'location' => $location,
            'sublocation' => $sublocation,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function order(User $user, float $total = 100, array $attributes = []): Order
    {
        return Order::factory()->create(array_merge([
            'portal_user_id' => $user->id,
            'company_id' => $user->company_id,
            'subtotal' => $total,
            'grand_total' => $total,
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
            'created_at' => '2026-01-15 12:00:00',
        ], $attributes));
    }

    public static function line(Order $order, ?Location $location, float $unitPrice, float $quantity = 1, ?Product $product = null): OrderItem
    {
        $product ??= Product::factory()->create();

        return OrderItem::forceCreate([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'location_id' => $location?->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_discount' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function payment(Order $order, array $attributes = []): Payment
    {
        return Payment::forceCreate(array_merge([
            'order_id' => $order->id,
            'method' => 'card',
            'status' => PaymentStatus::Paid,
            'amount' => $order->grand_total,
            'currency' => 'USD',
            'gateway_charge_id' => 'ch_secret_'.Str::random(6),
            'paid_at' => '2026-01-15 12:05:00',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function shipment(Order $order, array $attributes = []): Shipment
    {
        return Shipment::forceCreate(array_merge([
            'order_id' => $order->id,
            'carrier' => 'UPS',
            'service' => 'Ground',
            'tracking_number' => '1Z'.Str::upper(Str::random(10)),
            'status' => 'delivered',
            'shipped_at' => '2026-01-16 09:00:00',
            'delivered_at' => '2026-01-19 15:00:00',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function quote(User $user, float $total = 250, array $attributes = []): Quote
    {
        return Quote::factory()->create(array_merge([
            'portal_user_id' => $user->id,
            'company_id' => $user->company_id,
            'customer_id' => null,
            'status' => 'sent',
            'subtotal' => $total,
            'tax_total' => 0,
            'discount_amount' => 0,
            'grand_total' => $total,
            'created_at' => '2026-01-10 10:00:00',
        ], $attributes));
    }

    public static function quoteLine(Quote $quote, ?Location $location, float $lineTotal, float $quantity = 1): QuoteItem
    {
        $product = Product::factory()->create();

        return QuoteItem::forceCreate([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'location_id' => $location?->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'qty' => $quantity,
            'unit_price' => $lineTotal / $quantity,
            'line_subtotal' => $lineTotal,
            'line_tax' => 0,
            'line_total' => $lineTotal,
            'uom' => 'EA',
        ]);
    }

    /**
     * Validate and run a raw definition as the user.
     *
     * @param  array<string, mixed>  $definition
     */
    public static function run(User $user, array $definition, ?int $maxRows = null): ReportResult
    {
        $validated = app(ReportDefinitionValidator::class)->validated($definition);

        return app(ReportRunner::class)->run($user, $validated, $maxRows);
    }

    /**
     * The values of one column across all rows.
     *
     * @return list<mixed>
     */
    public static function column(ReportResult $result, string $key): array
    {
        return array_column($result->rows, $key);
    }
}
