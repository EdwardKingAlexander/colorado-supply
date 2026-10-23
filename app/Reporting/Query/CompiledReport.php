<?php

namespace App\Reporting\Query;

use Illuminate\Database\Query\Builder;

final class CompiledReport
{
    /**
     * @param  Builder  $query  selects each column as c0..cN, already scoped, filtered and sorted
     * @param  list<array{key: string, label: string, field: string, type: string, format: ?string, bucket: ?string, options: array<string, string>}>  $columns
     */
    public function __construct(
        public readonly Builder $query,
        public readonly array $columns,
    ) {}
}
