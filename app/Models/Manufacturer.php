<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manufacturer extends Model
{
    protected $fillable = [
        'name',
        'cage_code',
    ];

    public function milSpecParts(): HasMany
    {
        return $this->hasMany(MilSpecPart::class);
    }
}