<?php

namespace App\Reporting\Datasets;

use App\Models\User;
use App\Reporting\Fields\QuoteFields;
use Illuminate\Database\Query\Builder;

class QuotesDataset extends Dataset
{
    public function key(): string
    {
        return 'quotes';
    }

    public function label(): string
    {
        return 'Quotes';
    }

    public function description(): string
    {
        return 'One row per quote, including product-page quote requests.';
    }

    public function alias(): string
    {
        return 'q';
    }

    protected function table(): string
    {
        return 'quotes';
    }

    protected function joins(): array
    {
        return [];
    }

    protected function defineFields(): array
    {
        return QuoteFields::fields();
    }

    public function applyScope(Builder $query, User $user): void
    {
        $this->scopeToQuotes($query, $user);
    }
}
