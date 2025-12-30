<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationProduct extends Model
{
    use HasFactory;

    protected $table = 'location_products';

    protected $fillable = [
        'location_id',
        'product_id',
        'bin_label',
        'reorder_point',
        'max_stock',
        'on_hand',
        'visible',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
