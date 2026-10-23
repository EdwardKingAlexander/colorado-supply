<?php

namespace App\Reporting\Datasets;

use App\Models\User;
use App\Reporting\Fields\OrderFields;
use Illuminate\Database\Query\Builder;

class OrdersDataset extends Dataset
{
    public function key(): string
    {
        return 'orders';
    }

    public function label(): string
    {
        return 'Orders';
    }

    public function description(): string
    {
        return 'One row per order: statuses, dates and totals.';
    }

    public function alias(): string
    {
        return 'o';
    }

    protected function table(): string
    {
        return 'orders';
    }

    protected function joins(): array
    {
        return OrderFields::joins();
    }

    protected function defineFields(): array
    {
        return OrderFields::fields();
    }

    public function applyScope(Builder $query, User $user): void
    {
        $this->scopeToOrders($query, $user);
    }
}
