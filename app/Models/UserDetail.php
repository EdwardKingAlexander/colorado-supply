<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ship_to_address',
        'ship_to_city',
        'ship_to_state',
        'ship_to_zip',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_zip',
        'email',
        'first_name',
        'last_name',
        'company_name',
        'country',
        'state',
        'city',
        'zip_code',
        'fax_number',
        'address_line_1',
        'address_line_2',
        'phone_number',
        'mobile_number',
        'website',
        'tax_id',
        'notes',
        'last_interaction',
    ];

    /**
     * Get the user that owns the detail.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
