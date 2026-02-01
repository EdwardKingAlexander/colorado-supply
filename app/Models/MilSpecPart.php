<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MilSpecPart extends Model
{
    /** @use HasFactory<\Database\Factories\MilSpecPartFactory> */
    use HasFactory;

    protected $fillable = [
        'nsn',
        'description',
        'manufacturer_part_number',
        'manufacturer_id',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function procurementHistories(): HasMany
    {
        return $this->hasMany(ProcurementHistory::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)
                    ->withPivot('supplier_part_number');
    }
}
