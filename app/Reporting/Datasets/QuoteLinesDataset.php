<?php

namespace App\Reporting\Datasets;

use App\Models\User;
use App\Reporting\Fields\Field;
use App\Reporting\Fields\LocationFields;
use App\Reporting\Fields\ProductFields;
use App\Reporting\Fields\QuoteFields;
use App\Reporting\FieldType;
use Illuminate\Database\Query\Builder;

class QuoteLinesDataset extends Dataset
{
    public function key(): string
    {
        return 'quote_lines';
    }

    public function label(): string
    {
        return 'Quote lines';
    }

    public function description(): string
    {
        return 'One row per quoted item, with its quote, product and location.';
    }

    public function alias(): string
    {
        return 'qi';
    }

    protected function table(): string
    {
        return 'quote_items';
    }

    protected function requiredJoins(): array
    {
        return ['quote'];
    }

    protected function joins(): array
    {
        return [
            ...QuoteFields::joins('qi'),
            ...ProductFields::joins('qi'),
            ...LocationFields::joins('qi'),
        ];
    }

    protected function defineFields(): array
    {
        $line = fn (string $key, string $label, FieldType $type, string $expression) => new Field(
            "line.{$key}", $label, 'Line item', $type, $expression,
        );

        return [
            $line('sku', 'SKU', FieldType::String, 'qi.sku'),
            $line('name', 'Item name', FieldType::String, 'qi.name'),
            $line('quantity', 'Quantity', FieldType::Number, 'qi.qty'),
            $line('uom', 'Unit of measure', FieldType::String, 'qi.uom'),
            $line('unit_price', 'Unit price', FieldType::Money, 'qi.unit_price'),
            $line('line_subtotal', 'Line subtotal', FieldType::Money, 'qi.line_subtotal'),
            $line('line_tax', 'Line tax', FieldType::Money, 'qi.line_tax'),
            $line('line_total', 'Line total', FieldType::Money, 'qi.line_total'),
            ...LocationFields::fields('qi'),
            ...ProductFields::fields(),
            ...QuoteFields::fields(['quote']),
        ];
    }

    public function applyScope(Builder $query, User $user): void
    {
        $this->scopeToQuotes($query, $user);
    }
}
