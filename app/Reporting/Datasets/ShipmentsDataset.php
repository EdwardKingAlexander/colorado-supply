<?php

namespace App\Reporting\Datasets;

use App\Models\User;
use App\Reporting\Fields\Field;
use App\Reporting\Fields\OrderFields;
use App\Reporting\FieldType;
use App\Reporting\Sql\SqlDialect;
use Closure;
use Illuminate\Database\Query\Builder;

class ShipmentsDataset extends Dataset
{
    public const STATUSES = [
        'pending' => 'Pending',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'returned' => 'Returned',
    ];

    public function key(): string
    {
        return 'shipments';
    }

    public function label(): string
    {
        return 'Shipments';
    }

    public function description(): string
    {
        return 'One row per shipment: carrier, status and delivery times.';
    }

    public function alias(): string
    {
        return 'sh';
    }

    protected function table(): string
    {
        return 'shipments';
    }

    protected function requiredJoins(): array
    {
        return ['order'];
    }

    protected function joins(): array
    {
        return OrderFields::joins('sh');
    }

    protected function defineFields(): array
    {
        $shipment = fn (string $key, string $label, FieldType $type, string|Closure $expression, array $options = []) => new Field(
            "shipment.{$key}", $label, 'Shipment', $type, $expression, options: $options,
        );

        return [
            new Field('shipment.id', 'Shipment ID', 'Shipment', FieldType::Id, 'sh.id', filterable: false),
            $shipment('carrier', 'Carrier', FieldType::String, 'sh.carrier'),
            $shipment('service', 'Service', FieldType::String, 'sh.service'),
            $shipment('tracking_number', 'Tracking number', FieldType::String, 'sh.tracking_number'),
            $shipment('status', 'Shipment status', FieldType::Enum, 'sh.status', self::STATUSES),
            $shipment('created_at', 'Shipment created', FieldType::DateTime, 'sh.created_at'),
            $shipment('shipped_at', 'Shipped at', FieldType::DateTime, 'sh.shipped_at'),
            $shipment('delivered_at', 'Delivered at', FieldType::DateTime, 'sh.delivered_at'),
            $shipment('returned_at', 'Returned at', FieldType::DateTime, 'sh.returned_at'),
            $shipment('days_to_deliver', 'Days to deliver', FieldType::Number,
                fn (SqlDialect $dialect) => $dialect->dateDiffDays('sh.delivered_at', 'sh.shipped_at')),
            ...OrderFields::fields(['order']),
        ];
    }

    public function applyScope(Builder $query, User $user): void
    {
        $this->scopeToOrders($query, $user);
    }
}
