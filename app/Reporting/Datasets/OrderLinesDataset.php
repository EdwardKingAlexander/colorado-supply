<?php

namespace App\Reporting\Datasets;

use App\Models\User;
use App\Reporting\Fields\Field;
use App\Reporting\Fields\LocationFields;
use App\Reporting\Fields\OrderFields;
use App\Reporting\Fields\ProductFields;
use App\Reporting\FieldType;
use Illuminate\Database\Query\Builder;

class OrderLinesDataset extends Dataset
{
    public function key(): string
    {
        return 'order_lines';
    }

    public function label(): string
    {
        return 'Order lines';
    }

    public function description(): string
    {
        return 'One row per ordered item, with its order, product and location.';
    }

    public function alias(): string
    {
        return 'li';
    }

    protected function table(): string
    {
        return 'order_items';
    }

    protected function requiredJoins(): array
    {
        return ['order'];
    }

    protected function joins(): array
    {
        return [
            ...OrderFields::joins('li'),
            ...ProductFields::joins('li'),
            ...LocationFields::joins('li'),
        ];
    }

    protected function defineFields(): array
    {
        $line = fn (string $key, string $label, FieldType $type, string $expression) => new Field(
            "line.{$key}", $label, 'Line item', $type, $expression,
        );

        return [
            $line('sku', 'SKU', FieldType::String, 'li.sku'),
            $line('name', 'Item name', FieldType::String, 'li.name'),
            $line('quantity', 'Quantity', FieldType::Number, 'li.quantity'),
            $line('unit_price', 'Unit price', FieldType::Money, 'li.unit_price'),
            $line('line_discount', 'Line discount', FieldType::Money, 'li.line_discount'),
            $line('line_total', 'Line total', FieldType::Money, 'li.line_total'),
            ...LocationFields::fields('li'),
            ...ProductFields::fields(),
            ...OrderFields::fields(['order']),
        ];
    }

    public function applyScope(Builder $query, User $user): void
    {
        $this->scopeToOrders($query, $user);
    }
}
