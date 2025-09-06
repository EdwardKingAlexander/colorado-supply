<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendor extends Model
{

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'slug',
        'description',
        'logo',
    ];


    /**
     * Get the user that owns the vendor.
     */
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the products for the vendor.
     */
    public function products() : HasMany
    {
        return $this->hasMany(Product::class);
    }



}
