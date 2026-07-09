<?php

namespace App\Services\Dashboard;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Location;
use App\Models\Order;
use App\Models\User;
use App\Support\Dashboard\DashboardDateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserDashboardDataService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dataFor(User $user, array $filters = []): array
    {
        $dateRange = DashboardDateRange::fromFilters($filters);
        $locations = $this->locationsFor($user);

        $orders = $this->baseOrdersQuery($user, $filters, $dateRange);
        $ordersForTotals = (clone $orders)->get();
        $orderIds = $ordersForTotals->pluck('id');

        return [
            'filters' => [
                'range' => $filters['range'] ?? $dateRange->key,
                'start_date' => $dateRange->start->toDateString(),
                'end_date' => $dateRange->end->toDateString(),
                'location_id' => $filters['location_id'] ?? null,
                'sublocation_id' => $filters['sublocation_id'] ?? null,
                'status' => $filters['status'] ?? null,
                'payment_status' => $filters['payment_status'] ?? null,
                'options' => $this->filterOptions(),
            ],
            'account' => $this->account($user, $locations),
            'summary' => $this->summary($ordersForTotals),
            'charts' => [
                'spend_over_time' => $this->spendOverTime($orderIds, $dateRange),
                'spend_by_location' => $this->spendByLocation($orderIds),
                'spend_by_sublocation' => $this->spendBySublocation($orderIds, $filters),
                'payment_status_breakdown' => $this->paymentStatusBreakdown($ordersForTotals),
            ],
            'recent_orders' => $this->recentOrders($user),
            'top_items' => $this->topItems($orderIds),
            'locations' => $this->locationOptions($locations),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function baseOrdersQuery(User $user, array $filters, DashboardDateRange $dateRange): Builder
    {
        return Order::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$dateRange->start, $dateRange->end])
            ->where(function (Builder $query) use ($user) {
                if ($user->company_id) {
                    $query->where('company_id', $user->company_id)
                        ->orWhere('portal_user_id', $user->id);

                    return;
                }

                $query->where('portal_user_id', $user->id);
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['payment_status'] ?? null, fn (Builder $query, string $status) => $query->where('payment_status', $status))
            ->when($filters['location_id'] ?? null, function (Builder $query, int|string $locationId) {
                $query->whereHas('items.location', fn (Builder $locationQuery) => $locationQuery
                    ->where('locations.id', $locationId)
                    ->orWhere('locations.parent_id', $locationId));
            })
            ->when($filters['sublocation_id'] ?? null, fn (Builder $query, int|string $locationId) => $query
                ->whereHas('items', fn (Builder $itemQuery) => $itemQuery->where('location_id', $locationId)));
    }

    /**
     * @return Collection<int, Location>
     */
    private function locationsFor(User $user): Collection
    {
        if (! $user->company_id) {
            return collect();
        }

        return Location::query()
            ->with(['parent', 'children'])
            ->where('company_id', $user->company_id)
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, Location>  $locations
     * @return array<string, mixed>
     */
    private function account(User $user, Collection $locations): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'company' => $user->company ? [
                'id' => $user->company->id,
                'name' => $user->company->name,
                'slug' => $user->company->slug,
            ] : null,
            'locations_count' => $locations->whereNull('parent_id')->count(),
            'sublocations_count' => $locations->whereNotNull('parent_id')->count(),
            'profile_complete' => filled($user->name) && filled($user->email) && $user->company_id !== null,
        ];
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array<string, mixed>
     */
    private function summary(Collection $orders): array
    {
        $ordersCount = $orders->count();
        $totalSpend = (float) $orders
            ->where('payment_status', PaymentStatus::Paid)
            ->sum(fn (Order $order) => (float) $order->grand_total);

        return [
            'total_spend' => round($totalSpend, 2),
            'orders_count' => $ordersCount,
            'average_order_value' => $ordersCount > 0 ? round((float) $orders->sum(fn (Order $order) => (float) $order->grand_total) / $ordersCount, 2) : 0.0,
            'open_orders_count' => $orders->where('status', OrderStatus::Draft)->count(),
            'unpaid_orders_count' => $orders->whereIn('payment_status', [PaymentStatus::Unpaid, PaymentStatus::Pending])->count(),
            'top_location' => $this->topLocation($orders->pluck('id')),
        ];
    }

    /**
     * @param  Collection<int, int>  $orderIds
     * @return array<int, array{label: string, total: float}>
     */
    private function spendOverTime(Collection $orderIds, DashboardDateRange $dateRange): array
    {
        if ($orderIds->isEmpty()) {
            return [];
        }

        $dateExpression = $dateRange->bucketFormat() === 'Y-m'
            ? "strftime('%Y-%m', orders.created_at)"
            : 'date(orders.created_at)';

        return DB::table('orders')
            ->whereIn('id', $orderIds)
            ->where('payment_status', PaymentStatus::Paid->value)
            ->selectRaw("{$dateExpression} as bucket, SUM(grand_total) as total")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->map(fn ($row) => [
                'label' => $dateRange->bucketLabel($row->bucket),
                'total' => round((float) $row->total, 2),
            ])
            ->all();
    }

    /**
     * @param  Collection<int, int>  $orderIds
     * @return array<int, array{location_id: int|null, label: string, total: float}>
     */
    private function spendByLocation(Collection $orderIds): array
    {
        if ($orderIds->isEmpty()) {
            return [];
        }

        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('locations as leaf_locations', 'leaf_locations.id', '=', 'order_items.location_id')
            ->leftJoin('locations as parent_locations', 'parent_locations.id', '=', 'leaf_locations.parent_id')
            ->whereIn('order_items.order_id', $orderIds)
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->selectRaw('COALESCE(parent_locations.id, leaf_locations.id) as location_id')
            ->selectRaw("COALESCE(parent_locations.name, leaf_locations.name, 'Unassigned') as label")
            ->selectRaw('SUM(order_items.line_total) as total')
            ->groupBy('location_id', 'label')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'location_id' => $row->location_id ? (int) $row->location_id : null,
                'label' => $row->label,
                'total' => round((float) $row->total, 2),
            ])
            ->all();
    }

    /**
     * @param  Collection<int, int>  $orderIds
     * @param  array<string, mixed>  $filters
     * @return array<int, array{location_id: int|null, parent_id: int|null, label: string, total: float}>
     */
    private function spendBySublocation(Collection $orderIds, array $filters): array
    {
        if ($orderIds->isEmpty()) {
            return [];
        }

        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('locations', 'locations.id', '=', 'order_items.location_id')
            ->whereIn('order_items.order_id', $orderIds)
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->when($filters['location_id'] ?? null, fn ($query, $locationId) => $query
                ->where(fn ($inner) => $inner
                    ->where('locations.id', $locationId)
                    ->orWhere('locations.parent_id', $locationId)))
            ->selectRaw('locations.id as location_id')
            ->selectRaw('locations.parent_id as parent_id')
            ->selectRaw("COALESCE(locations.name, 'Unassigned') as label")
            ->selectRaw('SUM(order_items.line_total) as total')
            ->groupBy('location_id', 'parent_id', 'label')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'location_id' => $row->location_id ? (int) $row->location_id : null,
                'parent_id' => $row->parent_id ? (int) $row->parent_id : null,
                'label' => $row->label,
                'total' => round((float) $row->total, 2),
            ])
            ->all();
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array<int, array{label: string, value: string, count: int, total: float}>
     */
    private function paymentStatusBreakdown(Collection $orders): array
    {
        return $orders
            ->groupBy(fn (Order $order) => $order->payment_status->value)
            ->map(fn (Collection $statusOrders, string $status) => [
                'label' => PaymentStatus::from($status)->label(),
                'value' => $status,
                'count' => $statusOrders->count(),
                'total' => round((float) $statusOrders->sum(fn (Order $order) => (float) $order->grand_total), 2),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentOrders(User $user): array
    {
        return Order::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($user) {
                if ($user->company_id) {
                    $query->where('company_id', $user->company_id)
                        ->orWhere('portal_user_id', $user->id);

                    return;
                }

                $query->where('portal_user_id', $user->id);
            })
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'payment_status' => $order->payment_status->value,
                'payment_status_label' => $order->payment_status->label(),
                'grand_total' => (float) $order->grand_total,
                'created_at' => $order->created_at?->toDateString(),
            ])
            ->all();
    }

    /**
     * @param  Collection<int, int>  $orderIds
     * @return array<int, array{name: string, sku: string|null, quantity: float, total: float}>
     */
    private function topItems(Collection $orderIds): array
    {
        if ($orderIds->isEmpty()) {
            return [];
        }

        return DB::table('order_items')
            ->whereIn('order_id', $orderIds)
            ->select('name', 'sku')
            ->selectRaw('SUM(quantity) as quantity')
            ->selectRaw('SUM(line_total) as total')
            ->groupBy('name', 'sku')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'sku' => $row->sku,
                'quantity' => (float) $row->quantity,
                'total' => round((float) $row->total, 2),
            ])
            ->all();
    }

    /**
     * @param  Collection<int, int>  $orderIds
     * @return array{location_id: int|null, label: string, total: float}|null
     */
    private function topLocation(Collection $orderIds): ?array
    {
        return $this->spendByLocation($orderIds)[0] ?? null;
    }

    /**
     * @param  Collection<int, Location>  $locations
     * @return array<int, array<string, mixed>>
     */
    private function locationOptions(Collection $locations): array
    {
        return $locations
            ->whereNull('parent_id')
            ->values()
            ->map(fn (Location $location) => [
                'id' => $location->id,
                'name' => $location->name,
                'children' => $locations
                    ->where('parent_id', $location->id)
                    ->values()
                    ->map(fn (Location $child) => [
                        'id' => $child->id,
                        'name' => $child->name,
                    ])
                    ->all(),
            ])
            ->all();
    }

    /**
     * @return array<string, array<int, array{label: string, value: string}>>
     */
    private function filterOptions(): array
    {
        return [
            'ranges' => [
                ['label' => 'This month', 'value' => 'this_month'],
                ['label' => 'Last 30 days', 'value' => 'last_30_days'],
                ['label' => 'Quarter to date', 'value' => 'quarter_to_date'],
                ['label' => 'Year to date', 'value' => 'year_to_date'],
                ['label' => 'Last 12 months', 'value' => 'last_12_months'],
                ['label' => 'Custom', 'value' => 'custom'],
            ],
            'statuses' => array_map(fn (OrderStatus $status) => [
                'label' => $status->label(),
                'value' => $status->value,
            ], OrderStatus::cases()),
            'payment_statuses' => array_map(fn (PaymentStatus $status) => [
                'label' => $status->label(),
                'value' => $status->value,
            ], PaymentStatus::cases()),
        ];
    }
}
