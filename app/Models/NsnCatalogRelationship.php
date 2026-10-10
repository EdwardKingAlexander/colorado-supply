<?php

namespace App\Models;

use App\Models\Concerns\UsesPubLogConnection;
use Illuminate\Database\Eloquent\Model;

class NsnCatalogRelationship extends Model
{
    use UsesPubLogConnection;

    public $timestamps = false;

    protected $guarded = [];

    protected $appends = ['data'];

    public function getDataAttribute(): array
    {
        $fields = $this->kind === 'standardization'
            ? ['NIIN', 'RELATED_NSN', 'ISC', 'ORIG_STDZN_DEC', 'DT_STDZN_DEC', 'NIIN_STAT_CD']
            : ['NIIN', 'CANCELLED_NIIN_FSC', 'CANCELLED_NIIN', 'NIIN_STAT_CD', 'EFF_DATE', 'DEMIL'];
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = $this->getAttribute(strtolower($field));
        }

        return $data;
    }

    public function getRowHashAttribute(string $value): string
    {
        return bin2hex($value);
    }
}
