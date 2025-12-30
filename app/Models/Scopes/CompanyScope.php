<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (Auth::check() && $user = Auth::user()) {
            if ($user->company_id) {
                $companyId = $user->company_id;
                $table = $model->getTable();

                if ($table === 'products') {
                    $builder->whereIn($table.'.id', function ($query) use ($companyId) {
                        $query->select('product_id')
                            ->from('company_products')
                            ->where('company_id', $companyId);
                    });
                } elseif (Schema::hasColumn($table, 'company_id')) {
                    $builder->where($table.'.company_id', $companyId);
                }
            }
        }
    }
}
