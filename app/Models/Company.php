<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'domain_enforcement_enabled_at',
    ];

    protected function casts(): array
    {
        return [
            'domain_enforcement_enabled_at' => 'datetime',
        ];
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function emailDomains(): HasMany
    {
        return $this->hasMany(CompanyEmailDomain::class);
    }

    public function primaryEmailDomain(): HasOne
    {
        return $this->hasOne(CompanyEmailDomain::class)->where('is_primary', true);
    }

    public function enforcesEmailDomains(): bool
    {
        return $this->domain_enforcement_enabled_at !== null;
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'company_products')->withPivot('price');
    }

    public function locationProducts()
    {
        return $this->hasManyThrough(LocationProduct::class, Location::class);
    }
}
