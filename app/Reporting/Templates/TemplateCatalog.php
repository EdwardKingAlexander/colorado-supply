<?php

namespace App\Reporting\Templates;

/**
 * Ready-made report definitions anyone can start from. They live in code, not
 * the database, so they improve with each deploy; starting from one copies
 * its definition into a new report.
 */
final class TemplateCatalog
{
    /**
     * @return list<array{key: string, name: string, description: string, group: string, definition: array<string, mixed>}>
     */
    public static function all(): array
    {
        $last30 = self::dateFilter('order.created_at', 'last_30_days');

        $legacy = [
            'month' => ['Spend by month', 'Orders and spend per month.'],
            'location' => ['Spend by location', 'Orders, quantity and spend per location, sublocations rolled up.'],
            'sublocation' => ['Spend by sublocation', 'Orders, quantity and spend per sublocation.'],
            'product' => ['Spend by product', 'Orders, quantity and spend per item.'],
            'order' => ['Order list', 'Every order with its date, statuses and total.'],
        ];

        $templates = [];

        foreach ($legacy as $groupBy => [$name, $description]) {
            $templates[] = self::template("purchasing_{$groupBy}", $name, $description, 'Purchasing', [
                ...LegacyPresets::shape($groupBy),
                'filters' => $last30,
            ]);
        }

        return [...$templates, ...self::starters()];
    }

    /**
     * @return ?array{key: string, name: string, description: string, group: string, definition: array<string, mixed>}
     */
    public static function find(string $key): ?array
    {
        foreach (self::all() as $template) {
            if ($template['key'] === $key) {
                return $template;
            }
        }

        return null;
    }

    /**
     * @return list<array{key: string, name: string, description: string, group: string, definition: array<string, mixed>}>
     */
    private static function starters(): array
    {
        return [
            self::template('spend_by_month_by_location', 'Spend by month and location', 'Monthly spend with a column per location, with totals.', 'Purchasing', [
                'dataset' => 'order_lines',
                'mode' => 'pivot',
                'columns' => [
                    ['key' => 'month', 'field' => 'order.created_at', 'bucket' => 'month', 'label' => 'Month'],
                    ['key' => 'location', 'field' => 'location.parent_name'],
                    ['key' => 'spend', 'field' => 'line.line_total', 'aggregate' => 'sum', 'label' => 'Spend', 'format' => 'currency'],
                ],
                'pivot' => ['rows' => ['month'], 'column' => 'location', 'values' => ['spend']],
                'filters' => self::dateFilter('order.created_at', 'this_year'),
                'sort' => [['column' => 'month', 'dir' => 'asc']],
                'totals' => ['grand' => true, 'subtotals' => false],
            ]),
            self::template('top_products', 'Top 25 products', 'The 25 items you spend most on, with their share of total spend.', 'Purchasing', [
                'dataset' => 'order_lines',
                'mode' => 'summary',
                'columns' => [
                    ['key' => 'product', 'field' => 'line.name', 'label' => 'Product'],
                    ['key' => 'sku', 'field' => 'line.sku'],
                    ['key' => 'quantity', 'field' => 'line.quantity', 'aggregate' => 'sum', 'label' => 'Quantity'],
                    ['key' => 'spend', 'field' => 'line.line_total', 'aggregate' => 'sum', 'label' => 'Spend', 'format' => 'currency'],
                ],
                'post' => [['key' => 'share', 'column' => 'spend', 'op' => 'percent_of_total', 'label' => 'Share of spend']],
                'filters' => self::dateFilter('order.created_at', 'last_90_days'),
                'sort' => [['column' => 'spend', 'dir' => 'desc']],
                'limit' => 25,
            ]),
            self::template('open_orders', 'Open orders', 'Confirmed orders that have not been fully fulfilled, oldest first.', 'Orders', [
                'dataset' => 'orders',
                'mode' => 'table',
                'columns' => [
                    ['key' => 'number', 'field' => 'order.order_number'],
                    ['key' => 'date', 'field' => 'order.created_at', 'bucket' => 'day', 'label' => 'Order date'],
                    ['key' => 'po', 'field' => 'order.po_number'],
                    ['key' => 'fulfillment', 'field' => 'order.fulfillment_status'],
                    ['key' => 'payment', 'field' => 'order.payment_status'],
                    ['key' => 'total', 'field' => 'order.grand_total', 'format' => 'currency'],
                ],
                'filters' => ['logic' => 'and', 'rules' => [
                    ['field' => 'order.status', 'op' => 'eq', 'value' => 'confirmed'],
                    ['field' => 'order.fulfillment_status', 'op' => 'in', 'value' => ['unfulfilled', 'partially_fulfilled']],
                ]],
                'sort' => [['column' => 'date', 'dir' => 'asc']],
                'totals' => ['grand' => true, 'subtotals' => false],
            ]),
            self::template('payment_status_summary', 'Payments by status', 'How many payments are paid, pending, failed or refunded, and for how much.', 'Payments', [
                'dataset' => 'payments',
                'mode' => 'summary',
                'columns' => [
                    ['key' => 'status', 'field' => 'payment.status'],
                    ['key' => 'payments', 'field' => 'payment.id', 'aggregate' => 'count', 'label' => 'Payments'],
                    ['key' => 'amount', 'field' => 'payment.amount', 'aggregate' => 'sum', 'label' => 'Amount', 'format' => 'currency'],
                ],
                'filters' => self::dateFilter('payment.created_at', 'last_90_days'),
                'sort' => [['column' => 'amount', 'dir' => 'desc']],
                'totals' => ['grand' => true, 'subtotals' => false],
            ]),
            self::template('delivery_times_by_carrier', 'Delivery times by carrier', 'Average and slowest days from shipping to delivery, per carrier.', 'Shipping', [
                'dataset' => 'shipments',
                'mode' => 'summary',
                'columns' => [
                    ['key' => 'carrier', 'field' => 'shipment.carrier'],
                    ['key' => 'shipments', 'field' => 'shipment.id', 'aggregate' => 'count', 'label' => 'Shipments'],
                    ['key' => 'average_days', 'field' => 'shipment.days_to_deliver', 'aggregate' => 'avg', 'label' => 'Average days', 'format' => 'number'],
                    ['key' => 'slowest_days', 'field' => 'shipment.days_to_deliver', 'aggregate' => 'max', 'label' => 'Slowest (days)'],
                ],
                'filters' => ['logic' => 'and', 'rules' => [
                    ['field' => 'shipment.delivered_at', 'op' => 'relative', 'value' => 'last_90_days'],
                    ['field' => 'shipment.shipped_at', 'op' => 'not_null'],
                ]],
                'sort' => [['column' => 'average_days', 'dir' => 'asc']],
            ]),
            self::template('quote_conversion_by_month', 'Quote conversion by month', 'Quotes requested each month and how many became orders.', 'Quotes', [
                'dataset' => 'quotes',
                'mode' => 'summary',
                'columns' => [
                    ['key' => 'month', 'field' => 'quote.created_at', 'bucket' => 'month', 'label' => 'Month'],
                    ['key' => 'quotes', 'field' => 'quote.id', 'aggregate' => 'count', 'label' => 'Quotes'],
                    ['key' => 'converted', 'field' => 'quote.has_order', 'aggregate' => 'count_true', 'label' => 'Became orders'],
                    ['key' => 'quoted', 'field' => 'quote.grand_total', 'aggregate' => 'sum', 'label' => 'Quoted', 'format' => 'currency'],
                    ['key' => 'ordered', 'field' => 'quote.ordered_total', 'aggregate' => 'sum', 'label' => 'Ordered', 'format' => 'currency'],
                ],
                'calculated' => [['key' => 'conversion', 'label' => 'Conversion rate', 'formula' => 'converted / quotes', 'format' => 'percent']],
                'filters' => self::dateFilter('quote.created_at', 'this_year'),
                'sort' => [['column' => 'month', 'dir' => 'asc']],
                'totals' => ['grand' => true, 'subtotals' => false],
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array{key: string, name: string, description: string, group: string, definition: array<string, mixed>}
     */
    private static function template(string $key, string $name, string $description, string $group, array $definition): array
    {
        return ['key' => $key, 'name' => $name, 'description' => $description, 'group' => $group, 'definition' => ['version' => 1, ...$definition]];
    }

    /**
     * @return array<string, mixed>
     */
    private static function dateFilter(string $field, string $preset): array
    {
        return ['logic' => 'and', 'rules' => [['field' => $field, 'op' => 'relative', 'value' => $preset]]];
    }
}
