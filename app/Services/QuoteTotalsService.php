<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\QuoteItem;

class QuoteTotalsService
{
    public function recalculateTotals(Quote $quote): void
    {
        $items = $quote->items;

        if ($items->isEmpty()) {
            $quote->subtotal = 0.00;
            $quote->tax_total = 0.00;
            $quote->grand_total = 0.00;
            $quote->saveQuietly();
            return;
        }

        // Calculate subtotal from items
        $subtotal = $items->sum(function (QuoteItem $item) {
            $lineSubtotal = $this->round($item->qty * $item->unit_price);
            $item->line_subtotal = $lineSubtotal;
            return $lineSubtotal;
        });

        // Apply discount
        $discountAmount = $this->round($quote->discount_amount ?? 0);
        $subtotalAfterDiscount = $this->round($subtotal - $discountAmount);

        // Calculate tax
        $taxRate = $quote->tax_rate ?? 0;
        $taxTotal = $this->round($subtotalAfterDiscount * ($taxRate / 100));

        // Calculate item-level taxes and totals
        foreach ($items as $item) {
            $item->line_tax = $this->round($item->line_subtotal * ($taxRate / 100));
            $item->line_total = $this->round($item->line_subtotal + $item->line_tax);
            $item->saveQuietly();
        }

        // Calculate grand total
        $grandTotal = $this->round($subtotalAfterDiscount + $taxTotal);

        // Update quote totals
        $quote->subtotal = $this->round($subtotal);
        $quote->tax_total = $taxTotal;
        $quote->grand_total = $grandTotal;
        $quote->saveQuietly();
    }

    protected function round(float $value): float
    {
        return round($value, 2);
    }
}
