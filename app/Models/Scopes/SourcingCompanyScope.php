<?php

namespace App\Models\Scopes;

use App\Services\NsnSourcing\SourcingContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts private sourcing records to the acting company. Unlike
 * CompanyScope it also constrains admins (to the home company) and returns
 * nothing for a signed-in user without a company. Unauthenticated code
 * (console, queued jobs) is not constrained and must pass explicit IDs.
 */
class SourcingCompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (SourcingContext::actor() === null) {
            return;
        }
        $companyId = SourcingContext::companyId();
        $companyId === null
            ? $builder->whereRaw('1 = 0')
            : $builder->where($model->qualifyColumn('company_id'), $companyId);
    }
}
