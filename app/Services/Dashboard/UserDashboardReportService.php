<?php

namespace App\Services\Dashboard;

use App\Models\Location;
use App\Models\Order;
use App\Models\User;
use App\Support\Dashboard\DashboardDateRange;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserDashboardReportService
{
    public function __construct(private UserDashboardDataService $dashboardData) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function reportFor(User $user, array $filters): array
    {
        $dateRange = DashboardDateRange::fromFilters($filters);
        $groupBy = $filters['group_by'] ?? 'month';
        $orderIds = $this->dashboardData->baseOrdersQuery($user, $filters, $dateRange)->pluck('id');
        $rows = $this->rows($orderIds, $groupBy, $dateRange);

        return [
            'filters' => array_merge($filters, $dateRange->toArray(), [
                'group_by' => $groupBy,
                'options' => $this->options($user),
            ]),
            'columns' => $this->columns($groupBy),
            'rows' => $rows->take(100)->values()->all(),
            'row_count' => $rows->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{columns: array<int, string>, rows: Collection<int, array<string, mixed>>}
     */
    public function exportFor(User $user, array $filters): array
    {
        $dateRange = DashboardDateRange::fromFilters($filters);
        $groupBy = $filters['group_by'] ?? 'month';
        $orderIds = $this->dashboardData->baseOrdersQuery($user, $filters, $dateRange)->pluck('id');

        return [
            'columns' => $this->columns($groupBy),
            'rows' => $this->rows($orderIds, $groupBy, $dateRange),
        ];
    }

    /**
     * @param  Collection<int, int>  $orderIds
     * @return Collection<int, array<string, mixed>>
     */
    private function rows(Collection $orderIds, string $groupBy, DashboardDateRange $dateRange): Collection
    {
        if ($orderIds->isEmpty()) {
            return collect();
        }

        return match ($groupBy) {
            'location' => $this->locationRows($orderIds),
            'sublocation' => $this->sublocationRows($orderIds),
            'product' => $this->productRows($orderIds),
            'order' => $this->orderRows($orderIds),
            default => $this->monthRows($orderIds, $dateRange),
        };
    }

    /**
     * @param  Collection<int, int>  $orderIds
     * @return Collection<int, array<string, mixed>>
     */
    private function monthRows(Collection $orderIds, DashboardDateRange $dateRange): Collection
    {
        $dateExpression = "strftime('%Y-%m', orders.created_at)";

        return DB::table('orders')
            ->whereIn('id', $orderIds)
            ->selectRaw("{$dateExpression} as period")
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(grand_total) as spend')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'period' => $dateRange->bucketLabel($row->period),
                'orders' => (int) $row->orders,
                'spend' => round((float) $row->spend, 2),
            ]);
    }

    /**
     * @param  Collection<int, int>  $orderIds
     * @return Collection<int, array<string, mixed>>
     */
    private function locationRows(Collection $orderIds): Collection
    {
        return DB::table('order_items')
            ->leftJoin('locations as leaf_locations', 'leaf_locations.id', '=', 'order_items.location_id')
            ->leftJoin('locations as parent_locations', 'parent_locations.id', '=', 'leaf_locations.parent_id')
            ->whereIn('order_items.order_id', $orderIds)
            ->selectRaw("COALESCE(parent_locations.name, leaf_locations.name, 'Unassigned') as location")
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as orders')
            ->selectRaw('SUM(order_items.quantity) as quantity')
            ->selectRaw('SUM(order_items.line_total) as spend')
            ->groupBy('location')
            ->orderByDesc('spend')
            ->get()
            ->map(fn ($row) => [
                'location' => $row->location,
                'orders' => (int) $row->orders,
                'quantity' => (float) $row->quantity,
                'spend' => round((float) $row->spend, 2),
            ]);
    }

    /**
     * @param  Collection<int, int>  $orderIds
     * @return Collection<int, array<string, mixed>>
     */
    private function sublocationRows(Collection $orderIds): Collection
    {
        return DB::table('order_items')
            ->leftJoin('locations as leaf_locations', 'leaf_locations.id', '=', 'order_items.location_id')
            ->leftJoin('locations as parent_locations', 'parent_locations.id', '=', 'leaf_locations.parent_id')
            ->whereIn('order_items.order_id', $orderIds)
            ->selectRaw("COALESCE(parent_locations.name, leaf_locations.name, 'Unassigned') as location")
            ->selectRaw("CASE WHEN parent_locations.id IS NULL THEN 'Primary' ELSE leaf_locations.name END as sublocation")
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as orders')
            ->selectRaw('SUM(order_items.quantity) as quantity')
            ->selectRaw('SUM(order_items.line_total) as spend')
            ->groupBy('location', 'sublocation')
            ->orderByDesc('spend')
            ->get()
            ->map(fn ($row) => [
                'location' => $row->location,
                'sublocation' => $row->sublocation,
                'orders' => (int) $row->orders,
                'quantity' => (float) $row->quantity,
                'spend' => round((float) $row->spend, 2),
            ]);
    }

    /**
     * @param  Collection<int, int>  $orderIds
     * @return Collection<int, array<string, mixed>>
     */
    private function productRows(Collection $orderIds): Collection
    {
        return DB::table('order_items')
            ->whereIn('order_id', $orderIds)
            ->select('name', 'sku')
            ->selectRaw('COUNT(DISTINCT order_id) as orders')
            ->selectRaw('SUM(quantity) as quantity')
            ->selectRaw('SUM(line_total) as spend')
            ->groupBy('name', 'sku')
            ->orderByDesc('spend')
            ->get()
            ->map(fn ($row) => [
                'product' => $row->name,
                'sku' => $row->sku,
                'orders' => (int) $row->orders,
                'quantity' => (float) $row->quantity,
                'spend' => round((float) $row->spend, 2),
            ]);
    }

    /**
     * @param  Collection<int, int>  $orderIds
     * @return Collection<int, array<string, mixed>>
     */
    private function orderRows(Collection $orderIds): Collection
    {
        return Order::withoutGlobalScopes()
            ->whereIn('id', $orderIds)
            ->latest()
            ->get()
            ->map(fn (Order $order) => [
                'order_number' => $order->order_number,
                'date' => $order->created_at?->toDateString(),
                'status' => $order->status->label(),
                'payment_status' => $order->payment_status->label(),
                'spend' => (float) $order->grand_total,
            ]);
    }

    /**
     * @return array<int, string>
     */
    private function columns(string $groupBy): array
    {
        return match ($groupBy) {
            'location' => ['location', 'orders', 'quantity', 'spend'],
            'sublocation' => ['location', 'sublocation', 'orders', 'quantity', 'spend'],
            'product' => ['product', 'sku', 'orders', 'quantity', 'spend'],
            'order' => ['order_number', 'date', 'status', 'payment_status', 'spend'],
            default => ['period', 'orders', 'spend'],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function options(User $user): array
    {
        $locations = $user->company_id
            ? Location::query()->where('company_id', $user->company_id)->orderBy('name')->get()
            : collect();

        return [
            'group_by' => [
                ['label' => 'Month', 'value' => 'month'],
                ['label' => 'Location', 'value' => 'location'],
                ['label' => 'Sublocation', 'value' => 'sublocation'],
                ['label' => 'Product', 'value' => 'product'],
                ['label' => 'Order', 'value' => 'order'],
            ],
            'locations' => $locations
                ->whereNull('parent_id')
                ->values()
                ->map(fn (Location $location) => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'children' => $locations->where('parent_id', $location->id)->values()->map(fn (Location $child) => [
                        'id' => $child->id,
                        'name' => $child->name,
                    ])->all(),
                ])
                ->all(),
        ];
    }
}
