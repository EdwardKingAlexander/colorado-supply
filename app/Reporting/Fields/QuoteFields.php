<?php

namespace App\Reporting\Fields;

use App\Reporting\FieldType;

/**
 * Quote fields, shared by Quotes and Quote Lines. Quotes are always aliased
 * `q`. Walk-in contact columns, notes and the sales rep are deliberately absent.
 */
final class QuoteFields
{
    public const STATUSES = [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'ordered' => 'Ordered',
        'cancelled' => 'Cancelled',
        'expired' => 'Expired',
    ];

    /**
     * Matches converted orders on the quote's own tenant, so a mis-linked
     * order from another company can never be counted.
     */
    private const CONVERTED_ORDERS = 'FROM orders co WHERE co.quote_id = q.id AND co.deleted_at IS NULL'
        .' AND (co.company_id = q.company_id OR co.portal_user_id = q.portal_user_id)';

    /**
     * @param  list<string>  $via  joins needed to reach `q` from the dataset root
     * @return list<Field>
     */
    public static function fields(array $via = []): array
    {
        $field = fn (string $key, string $label, FieldType $type, string $expression, array $options = []) => new Field(
            "quote.{$key}", $label, 'Quote', $type, $expression, $via, $options,
        );

        return [
            new Field('quote.id', 'Quote ID', 'Quote', FieldType::Id, 'q.id', $via, filterable: false),
            $field('quote_number', 'Quote number', FieldType::String, 'q.quote_number'),
            $field('status', 'Quote status', FieldType::Enum, 'q.status', self::STATUSES),
            $field('created_at', 'Quote date', FieldType::DateTime, 'q.created_at'),
            $field('subtotal', 'Quote subtotal', FieldType::Money, 'q.subtotal'),
            $field('tax_total', 'Quote tax', FieldType::Money, 'q.tax_total'),
            $field('discount_amount', 'Quote discount', FieldType::Money, 'q.discount_amount'),
            $field('grand_total', 'Quote total', FieldType::Money, 'q.grand_total'),
            // ProductQuoteRequestService tags product-page requests this way.
            $field('is_product_request', 'Requested from product page', FieldType::Boolean,
                "CASE WHEN q.walk_in_label = 'product-page-quote' THEN 1 ELSE 0 END"),
            $field('has_order', 'Converted to order', FieldType::Boolean,
                'CASE WHEN EXISTS (SELECT 1 '.self::CONVERTED_ORDERS.') THEN 1 ELSE 0 END'),
            $field('ordered_total', 'Ordered total', FieldType::Money,
                '(SELECT COALESCE(SUM(co.grand_total), 0) '.self::CONVERTED_ORDERS.')'),
        ];
    }

    /**
     * @return array<string, Join>
     */
    public static function joins(string $fromAlias): array
    {
        return ['quote' => new Join('quotes', 'q', 'q.id', "{$fromAlias}.quote_id", inner: true)];
    }
}
