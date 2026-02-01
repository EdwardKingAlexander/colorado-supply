<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'cage_code',
        'contact_info',
        'website',
    ];

    public function procurementHistories(): HasMany
    {
        return $this->hasMany(ProcurementHistory::class);
    }

    public function milSpecParts(): BelongsToMany
    {
        return $this->belongsToMany(MilSpecPart::class)
                    ->withPivot('supplier_part_number');
    }
}