<?php

namespace App\Reporting\Fields;

use App\Enums\FulfillmentMethod;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PickupStatus;
use App\Reporting\FieldType;

/**
 * Order fields, shared by every dataset that reaches an order. Orders are
 * always aliased `o`. Internal notes, cash/card contact details, meta and
 * staff ids are deliberately absent.
 */
final class OrderFields
{
    /**
     * @param  list<string>  $via  joins needed to reach `o` from the dataset root
     * @return list<Field>
     */
    public static function fields(array $via = []): array
    {
        $field = fn (string $key, string $label, FieldType $type, string $expression, array $options = []) => new Field(
            "order.{$key}", $label, 'Order', $type, $expression, $via, $options,
        );

        return [
            new Field('order.id', 'Order ID', 'Order', FieldType::Id, 'o.id', $via, filterable: false),
            $field('order_number', 'Order number', FieldType::String, 'o.order_number'),
            $field('po_number', 'PO number', FieldType::String, 'o.po_number'),
            $field('job_number', 'Job number', FieldType::String, 'o.job_number'),
            $field('contact_name', 'Ordered by', FieldType::String, 'o.contact_name'),
            $field('status', 'Order status', FieldType::Enum, 'o.status', Field::enumOptions(OrderStatus::class)),
            $field('payment_status', 'Payment status', FieldType::Enum, 'o.payment_status', Field::enumOptions(PaymentStatus::class)),
            $field('fulfillment_status', 'Fulfillment status', FieldType::Enum, 'o.fulfillment_status', Field::enumOptions(FulfillmentStatus::class)),
            $field('payment_method', 'Payment method', FieldType::Enum, 'o.payment_method', Field::enumOptions(PaymentMethod::class)),
            $field('fulfillment_method', 'Fulfillment method', FieldType::Enum, 'o.fulfillment_method', Field::enumOptions(FulfillmentMethod::class)),
            $field('pickup_status', 'Pickup status', FieldType::Enum, 'o.pickup_status', Field::enumOptions(PickupStatus::class)),
            $field('created_at', 'Order date', FieldType::DateTime, 'o.created_at'),
            $field('confirmed_at', 'Confirmed at', FieldType::DateTime, 'o.confirmed_at'),
            $field('paid_at', 'Paid at', FieldType::DateTime, 'o.paid_at'),
            $field('fulfilled_at', 'Fulfilled at', FieldType::DateTime, 'o.fulfilled_at'),
            $field('cancelled_at', 'Cancelled at', FieldType::DateTime, 'o.cancelled_at'),
            $field('subtotal', 'Order subtotal', FieldType::Money, 'o.subtotal'),
            $field('tax_total', 'Order tax', FieldType::Money, 'o.tax_total'),
            $field('shipping_total', 'Order shipping', FieldType::Money, 'o.shipping_total'),
            $field('discount_total', 'Order discount', FieldType::Money, 'o.discount_total'),
            $field('grand_total', 'Order total', FieldType::Money, 'o.grand_total'),
            new Field('order.pickup_location', 'Pickup location', 'Order', FieldType::String, 'pl.name', [...$via, 'pickup_location']),
        ];
    }

    /**
     * @param  ?string  $fromAlias  alias of the child row holding `order_id`; null when orders are the root
     * @return array<string, Join>
     */
    public static function joins(?string $fromAlias = null): array
    {
        $joins = [];

        if ($fromAlias !== null) {
            $joins['order'] = new Join('orders', 'o', 'o.id', "{$fromAlias}.order_id", inner: true);
        }

        $joins['pickup_location'] = new Join(
            'locations', 'pl', 'pl.id', 'o.pickup_location_id',
            dependsOn: $fromAlias !== null ? ['order'] : [],
        );

        return $joins;
    }
}
