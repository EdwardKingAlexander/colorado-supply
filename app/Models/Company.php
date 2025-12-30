<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
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
