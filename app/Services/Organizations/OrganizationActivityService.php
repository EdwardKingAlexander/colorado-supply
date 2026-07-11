<?php

namespace App\Services\Organizations;

use App\Models\ActivityLog;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrganizationActivityService
{
    public function forCompany(Company $company, int $perPage = 25): LengthAwarePaginator
    {
        return ActivityLog::query()
            ->where('company_id', $company->getKey())
            ->where('log_name', 'organization')
            ->with(['causer', 'subject'])
            ->latest('id')
            ->paginate(min(max($perPage, 1), 100))
            ->through(fn (ActivityLog $activity) => [
                'id' => $activity->getKey(),
                'event' => $activity->event,
                'description' => $activity->description,
                'subject_type' => class_basename($activity->subject_type ?? ''),
                'subject_id' => $activity->subject_id,
                'actor' => $activity->causer ? [
                    'type' => class_basename($activity->causer_type ?? ''),
                    'id' => $activity->causer_id,
                    'name' => $activity->causer->name ?? 'Unknown',
                ] : null,
                'changes' => $activity->changes->toArray(),
                'created_at' => $activity->created_at?->toISOString(),
            ]);
    }
}
