<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'mil_spec_part_id',
        'supplier_id',
        'price',
        'quantity',
        'acquisition_date',
        'source_url',
        'notes',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'price' => 'decimal:2',
    ];

    public function milSpecPart(): BelongsTo
    {
        return $this->belongsTo(MilSpecPart::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
