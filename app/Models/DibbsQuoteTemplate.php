<?php

namespace App\Models;

use App\Services\Dibbs\BatchQuoteFileParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DLA's pre-filled batch quote row for an RFQ (bq*.txt), kept verbatim so the
 * Phase 6 export fills vendor fields without re-creating DLA's own columns.
 */
class DibbsQuoteTemplate extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'delivery_days' => 'integer',
        ];
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(DibbsRfq::class, 'dibbs_rfq_id');
    }

    /**
     * @return array<int, string> 0-indexed columns (DLA column N = index N-1)
     */
    public function columns(): array
    {
        return BatchQuoteFileParser::splitRow($this->raw_row);
    }
}
