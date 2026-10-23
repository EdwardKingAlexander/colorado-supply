<?php

namespace App\Reporting\Templates;

use App\Support\Dashboard\DashboardDateRange;

/**
 * The five fixed groupings of the original purchasing report
 * (UserDashboardReportService) as report definitions, using the same column
 * keys so legacy links and exports keep their shape.
 *
 * One deliberate difference: a location filter here keeps only the lines at
 * that location, where the original kept whole orders that had any line there.
 */
final class LegacyPresets
{
    public const GROUPINGS = ['month', 'location', 'sublocation', 'product', 'order'];

    /**
     * @param  array<string, mixed>  $filters  DashboardReportRequest filters (range, dates, status, payment_status, location ids)
     * @return array<string, mixed>
     */
    public static function definition(string $groupBy, array $filters = []): array
    {
        $range = DashboardDateRange::fromFilters($filters);

        $rules = [['field' => 'order.created_at', 'op' => 'between', 'value' => [$range->start->toDateString(), $range->end->toDateString()]]];

        foreach (['status' => 'order.status', 'payment_status' => 'order.payment_status'] as $filter => $field) {
            if (filled($filters[$filter] ?? null)) {
                $rules[] = ['field' => $field, 'op' => 'eq', 'value' => $filters[$filter]];
            }
        }

        $lineLevel = in_array($groupBy, ['location', 'sublocation', 'product'], true);
        $location = $filters['sublocation_id'] ?? $filters['location_id'] ?? null;

        if ($lineLevel && filled($location)) {
            $rules[] = ['field' => 'location.id', 'op' => 'in', 'value' => [(int) $location]];
        }

        return [
            ...self::shape($groupBy),
            'filters' => ['logic' => 'and', 'rules' => $rules],
            'limit' => 10000,
        ];
    }

    /**
     * The grouping's columns and sort, without filters.
     *
     * @return array<string, mixed>
     */
    public static function shape(string $groupBy): array
    {
        $orders = ['field' => 'order.id', 'aggregate' => 'count_distinct', 'key' => 'orders', 'label' => 'Orders'];
        $quantity = ['field' => 'line.quantity', 'aggregate' => 'sum', 'key' => 'quantity', 'label' => 'Quantity'];
        $spend = ['field' => 'line.line_total', 'aggregate' => 'sum', 'key' => 'spend', 'label' => 'Spend', 'format' => 'currency'];
        $bySpend = [['column' => 'spend', 'dir' => 'desc']];

        return match ($groupBy) {
            'location' => [
                'dataset' => 'order_lines',
                'mode' => 'summary',
                'columns' => [['field' => 'location.parent_name', 'key' => 'location'], $orders, $quantity, $spend],
                'sort' => $bySpend,
            ],
            'sublocation' => [
                'dataset' => 'order_lines',
                'mode' => 'summary',
                'columns' => [
                    ['field' => 'location.parent_name', 'key' => 'location'],
                    ['field' => 'location.sublocation_name', 'key' => 'sublocation'],
                    $orders, $quantity, $spend,
                ],
                'sort' => $bySpend,
            ],
            'product' => [
                'dataset' => 'order_lines',
                'mode' => 'summary',
                'columns' => [['field' => 'line.name', 'key' => 'product', 'label' => 'Product'], ['field' => 'line.sku', 'key' => 'sku'], $orders, $quantity, $spend],
                'sort' => $bySpend,
            ],
            'order' => [
                'dataset' => 'orders',
                'mode' => 'table',
                'columns' => [
                    ['field' => 'order.order_number', 'key' => 'order_number'],
                    ['field' => 'order.created_at', 'bucket' => 'day', 'key' => 'date', 'label' => 'Date'],
                    ['field' => 'order.status', 'key' => 'status'],
                    ['field' => 'order.payment_status', 'key' => 'payment_status'],
                    ['field' => 'order.grand_total', 'key' => 'spend', 'label' => 'Spend', 'format' => 'currency'],
                ],
                'sort' => [['column' => 'date', 'dir' => 'desc']],
            ],
            default => [
                'dataset' => 'orders',
                'mode' => 'summary',
                'columns' => [
                    ['field' => 'order.created_at', 'bucket' => 'month', 'key' => 'period', 'label' => 'Period'],
                    ['field' => 'order.id', 'aggregate' => 'count', 'key' => 'orders', 'label' => 'Orders'],
                    ['field' => 'order.grand_total', 'aggregate' => 'sum', 'key' => 'spend', 'label' => 'Spend', 'format' => 'currency'],
                ],
                'sort' => [['column' => 'period', 'dir' => 'asc']],
            ],
        };
    }
}
