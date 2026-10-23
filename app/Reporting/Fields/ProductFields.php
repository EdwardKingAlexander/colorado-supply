<?php

namespace App\Reporting\Fields;

use App\Reporting\FieldType;

/**
 * Catalog attributes of the product on a line. Products are aliased `p`.
 * Cost, pricing tiers and the supplying vendor are deliberately absent.
 */
final class ProductFields
{
    /**
     * @return list<Field>
     */
    public static function fields(): array
    {
        $field = fn (string $key, string $label, FieldType $type, string $expression, array $joins = ['product']) => new Field(
            "product.{$key}", $label, 'Product', $type, $expression, $joins,
        );

        return [
            $field('sku', 'Product SKU', FieldType::String, 'p.sku'),
            $field('name', 'Product name', FieldType::String, 'p.name'),
            $field('category', 'Category', FieldType::String, 'cat.name', ['product', 'category']),
            $field('mpn', 'Manufacturer part number', FieldType::String, 'p.mpn'),
            $field('part_number', 'Part number', FieldType::String, 'p.part_number'),
            $field('nsn', 'NSN', FieldType::String, 'p.nsn'),
            $field('unit_of_measure', 'Unit of measure', FieldType::String, 'p.unit_of_measure'),
            $field('is_hazmat', 'Hazmat', FieldType::Boolean, 'p.is_hazmat'),
            $field('berry_compliant', 'Berry compliant', FieldType::Boolean, 'p.berry_compliant'),
            $field('taa_compliant', 'TAA compliant', FieldType::Boolean, 'p.taa_compliant'),
            $field('made_in_usa', 'Made in USA', FieldType::Boolean, 'p.made_in_usa'),
            $field('gsa_approved', 'GSA approved', FieldType::Boolean, 'p.gsa_approved'),
        ];
    }

    /**
     * @return array<string, Join>
     */
    public static function joins(string $lineAlias): array
    {
        return [
            'product' => new Join('products', 'p', 'p.id', "{$lineAlias}.product_id"),
            'category' => new Join('categories', 'cat', 'cat.id', 'p.category_id', dependsOn: ['product']),
        ];
    }
}
