<?php

namespace App\Models;

use App\Models\Concerns\UsesPubLogConnection;
use Illuminate\Database\Eloquent\Model;

class CageEntity extends Model
{
    use UsesPubLogConnection;

    public $timestamps = false;

    protected $guarded = [];

    protected $appends = ['code_valid'];

    public function getCodeValidAttribute(): bool
    {
        return preg_match('/^[A-Z0-9]{5}$/D', $this->cage_code ?? '') === 1;
    }

    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'address' => 'array',
        ];
    }
}
