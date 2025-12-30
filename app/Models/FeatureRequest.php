<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureRequest extends Model
{
    protected $fillable = [
        'title',
        'description',
        'context',
        'priority',
        'status',
        'generated_prompt',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'string',
            'status' => 'string',
        ];
    }
}
