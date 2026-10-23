<?php

namespace App\Reporting;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * The single rule for which orders and quotes a portal user may see: their
 * company's, plus any they placed themselves (only the latter when they have
 * no company). Shared by the dashboard and the report engine so they can't drift.
 */
final class TenantScope
{
    public static function apply(
        QueryBuilder|EloquentBuilder $query,
        User $user,
        string $companyColumn = 'company_id',
        string $userColumn = 'portal_user_id',
    ): void {
        $query->where(function ($scoped) use ($user, $companyColumn, $userColumn) {
            if ($user->company_id) {
                $scoped->where($companyColumn, $user->company_id)
                    ->orWhere($userColumn, $user->id);

                return;
            }

            $scoped->where($userColumn, $user->id);
        });
    }
}
