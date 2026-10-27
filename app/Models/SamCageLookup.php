<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A company as the SAM.gov Entity API describes it, by CAGE code (cached by CageDirectory).
 */
class SamCageLookup extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'found' => 'boolean',
            'fetched_at' => 'datetime',
        ];
    }

    public function displayName(): ?string
    {
        return $this->found ? ($this->dba_name ?: $this->legal_business_name) : null;
    }

    public function location(): ?string
    {
        if (! $this->found) {
            return null;
        }

        return collect([$this->city, $this->state])->filter()->implode(', ') ?: $this->country;
    }
}
