<?php

namespace App\Observers;

use App\Models\Quote;
use Illuminate\Support\Facades\Log;

class QuoteObserver
{
    public function creating(Quote $quote): void
    {
        // Auto-generate quote number if not set
        if (empty($quote->quote_number)) {
            $quote->quote_number = 'Q-' . strtoupper(uniqid());
        }

        // Set sales_rep_id to current user if not set
        if (empty($quote->sales_rep_id) && auth()->check()) {
            $quote->sales_rep_id = auth()->id();
        }
    }

    public function created(Quote $quote): void
    {
        Log::info('Quote created', [
            'quote_id' => $quote->id,
            'quote_number' => $quote->quote_number,
            'user_id' => auth()->id(),
        ]);
    }

    public function updated(Quote $quote): void
    {
        Log::info('Quote updated', [
            'quote_id' => $quote->id,
            'quote_number' => $quote->quote_number,
            'user_id' => auth()->id(),
        ]);
    }
}
