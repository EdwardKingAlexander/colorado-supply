<?php

namespace App\Services\Orders;

use App\Models\OrderSequence;
use Illuminate\Support\Facades\DB;

class OrderNumberGenerator
{
    /**
     * Generate the next unique order number.
     * Format: COGOV-YYYYMM-#####
     */
    public function next(): string
    {
        $period = now()->format('Ym'); // e.g., "202510"

        // Use atomic update with retry logic for concurrency safety
        $attempts = 0;
        $maxAttempts = 5;

        while ($attempts < $maxAttempts) {
            try {
                return DB::transaction(function () use ($period) {
                    // Get or create sequence for this period
                    $sequence = OrderSequence::firstOrCreate(
                        ['period' => $period],
                        ['last_number' => 0]
                    );

                    // Lock the row for update
                    $sequence = OrderSequence::where('id', $sequence->id)
                        ->lockForUpdate()
                        ->first();

                    // Increment the sequence
                    $nextNumber = $sequence->last_number + 1;
                    $sequence->update(['last_number' => $nextNumber]);

                    // Format: COGOV-YYYYMM-#####
                    return sprintf('COGOV-%s-%05d', $period, $nextNumber);
                });
            } catch (\Exception $e) {
                $attempts++;

                if ($attempts >= $maxAttempts) {
                    throw new \RuntimeException(
                        'Failed to generate unique order number after ' . $maxAttempts . ' attempts: ' . $e->getMessage()
                    );
                }

                // Wait a bit before retrying (exponential backoff)
                usleep(100000 * $attempts); // 100ms, 200ms, 300ms, etc.
            }
        }

        throw new \RuntimeException('Failed to generate unique order number');
    }
}
