<?php

namespace App\Services;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderConfirmationMail;
use App\Models\Admin;
use App\Models\CompanyProduct;
use App\Models\Order;
use App\Models\Product;
use App\Models\Scopes\CompanyScope;
use App\Models\User;
use App\Services\Orders\OrderNumberGenerator;
use App\Services\Store\ProductPriceResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class StoreCheckoutService
{
    public function __construct(
        private OrderNumberGenerator $orderNumberGenerator,
        private ProductPriceResolver $prices,
    ) {}

    public function createFromCart(User|Admin $user, array $validated): Order
    {
        $billingAddress = $validated['billing_address'];
        $shippingSameAsBilling = $validated['shipping_same_as_billing'] ?? true;
        $shippingAddress = $shippingSameAsBilling || empty($validated['shipping_address'])
            ? $billingAddress
            : $validated['shipping_address'];

        $lines = $this->priceLines($user, $validated['items']);

        $subtotal = array_reduce($lines, fn (float $carry, array $line) => $carry + $line['line_total'], 0.0);

        $order = DB::transaction(function () use ($user, $validated, $billingAddress, $shippingAddress, $subtotal, $lines) {
            $order = Order::create([
                'order_number' => $this->orderNumberGenerator->next(),
                'portal_user_id' => $user instanceof User ? $user->id : null,
                'company_id' => $user instanceof User ? $user->company_id : null,
                'contact_name' => $validated['contact_name'],
                'contact_email' => $validated['contact_email'],
                'contact_phone' => $validated['contact_phone'] ?? null,
                'company_name' => $validated['company_name'] ?? null,
                'po_number' => $validated['po_number'] ?? null,
                'job_number' => $validated['job_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'billing_address' => $billingAddress,
                'shipping_address' => $shippingAddress,
                'subtotal' => $subtotal,
                'tax_total' => 0,
                'shipping_total' => 0,
                'discount_total' => 0,
                'grand_total' => $subtotal,
                'tax_rate' => 0,
                'status' => OrderStatus::Draft,
                'payment_status' => PaymentStatus::Unpaid,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'location_id' => $line['location_id'],
                    'sku' => $line['product']->sku,
                    'name' => $line['product']->name,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_discount' => 0,
                    'line_total' => $line['line_total'],
                ]);
            }

            return $order;
        });

        if ($order->customer_email) {
            Mail::to($order->customer_email)
                ->send(new OrderConfirmationMail($order->load('items')));
        }

        return $order;
    }

    /**
     * Price every cart line on the server. The browser's cart price is never
     * trusted: the unit price comes from the catalog for this buyer and
     * quantity (company price > quantity tier > base). Quantities above stock
     * are allowed so they can be backordered.
     *
     * @return list<array{product: Product, quantity: int, unit_price: float, line_total: float, location_id: ?int}>
     *
     * @throws ValidationException
     */
    private function priceLines(User|Admin $user, array $items): array
    {
        $lines = [];
        $errors = [];

        foreach (array_values($items) as $index => $item) {
            $product = $this->purchasableProduct($user, (int) ($item['product_id'] ?? $item['id'] ?? 0));
            $quantity = (int) $item['quantity'];

            if (! $product) {
                $errors["items.{$index}.product_id"] = 'This product is no longer available. Remove it from your cart.';

                continue;
            }

            $minimum = max(1, (int) ($product->minimum_order_quantity ?: 1));

            if ($quantity < $minimum) {
                $errors["items.{$index}.quantity"] = "{$product->name} has a minimum order of {$minimum}.";

                continue;
            }

            $unitPrice = $this->prices->resolve($product, $user, $quantity)['unit_price'];

            if ($unitPrice === null) {
                $errors["items.{$index}.product_id"] = "{$product->name} needs a quote. Request a quote from its product page.";

                continue;
            }

            $lines[] = [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => round($unitPrice * $quantity, 2),
                'location_id' => $item['location_id'] ?? null,
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $lines;
    }

    /**
     * Active product the buyer may order: in their company's catalog
     * (company_products) for portal users with a company; any active product
     * for admins and users without a company, matching the storefront.
     */
    private function purchasableProduct(User|Admin $user, int $productId): ?Product
    {
        return Product::query()
            ->withoutGlobalScope(CompanyScope::class)
            ->whereKey($productId)
            ->where('is_active', true)
            ->when(
                $user instanceof User && $user->company_id,
                fn ($query) => $query->whereIn('id', CompanyProduct::query()
                    ->select('product_id')
                    ->where('company_id', $user->company_id)),
            )
            ->with('priceTiers')
            ->first();
    }
}
