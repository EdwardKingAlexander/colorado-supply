<?php

namespace App\Models;

use App\Models\Concerns\UsesPubLogConnection;
use Illuminate\Database\Eloquent\Model;

class NsnReference extends Model
{
    use UsesPubLogConnection;

    public $timestamps = false;

    protected $guarded = [];

    protected $appends = ['codes', 'cage_code_valid'];

    public function getCageCodeValidAttribute(): bool
    {
        return preg_match('/^[A-Z0-9]{5}$/D', $this->cage_code ?? '') === 1;
    }

    public function getCodesAttribute(): array
    {
        $codes = [];
        foreach (['CAGE_STATUS', 'RNCC', 'RNVC', 'DAC', 'RNAAC', 'RNFC', 'RNSC', 'RNJC', 'SADC', 'HCC', 'MSDS', 'MEDALS'] as $field) {
            $codes[$field] = $this->getAttribute(strtolower($field));
        }

        return $codes;
    }

    public function getRowHashAttribute(string $value): string
    {
        return bin2hex($value);
    }
}
