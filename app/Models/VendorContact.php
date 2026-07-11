<?php

namespace App\Models;

use Database\Factories\VendorContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorContact extends Model
{
    /** @use HasFactory<VendorContactFactory> */
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'name',
        'job_title',
        'email',
        'phone',
        'mobile_phone',
        'notes',
        'is_preferred',
    ];

    protected function casts(): array
    {
        return [
            'is_preferred' => 'boolean',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    protected static function booted(): void
    {
        static::saved(function (VendorContact $contact): void {
            if (! $contact->is_preferred) {
                return;
            }

            static::query()
                ->where('vendor_id', $contact->vendor_id)
                ->whereKeyNot($contact->getKey())
                ->where('is_preferred', true)
                ->update(['is_preferred' => false]);
        });
    }
}
