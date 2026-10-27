<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A DLA DIBBS daily file that was downloaded or uploaded (ai/modules/dibbs-rfq-tracking).
 */
class DibbsFile extends Model
{
    use HasFactory;

    public const TYPE_INDEX = 'in';

    public const TYPE_BATCH_QUOTE = 'bq';

    public const STATUS_PENDING = 'pending';

    public const STATUS_IMPORTED = 'imported';

    public const STATUS_FAILED = 'failed';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'posting_date' => 'date',
            'row_errors' => 'array',
            'imported_at' => 'datetime',
            'digest_sent_at' => 'datetime',
        ];
    }

    public function isImported(): bool
    {
        return $this->status === self::STATUS_IMPORTED;
    }
}
