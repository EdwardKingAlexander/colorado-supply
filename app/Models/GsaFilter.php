<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GsaFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'code',
        'description',
        'enabled',
    ];

    public function scopeNaics(Builder $query): Builder
    {
        return $query->where('type', 'naics');
    }

    public function scopePsc(Builder $query): Builder
    {
        return $query->where('type', 'psc');
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }
}
