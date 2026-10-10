<?php

namespace App\Models;

use App\Models\Concerns\UsesPubLogConnection;
use Illuminate\Database\Eloquent\Model;

class PubLogImport extends Model
{
    use UsesPubLogConnection;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'release_date' => 'immutable_date',
            'manifest' => 'array',
            'progress' => 'array',
            'validation' => 'array',
            'pinned' => 'boolean',
            'completed_at' => 'immutable_datetime',
            'activated_at' => 'immutable_datetime',
        ];
    }
}
