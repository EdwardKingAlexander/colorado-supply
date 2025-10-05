<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LostReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'lost_reason_id');
    }
}
