<?php

namespace App\Services\NsnSourcing;

use App\Models\Admin;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

/**
 * Who is acting on sourcing cases, and for which company.
 *
 * Admins have no company; they act for the home company (the app's single
 * operating company, same rule as DIBBS tracking). Users act for their own
 * company and are denied without one. A posted company_id is never trusted.
 */
class SourcingContext
{
    public static function actor(): ?Authenticatable
    {
        $user = Auth::user();

        return $user instanceof Admin || $user instanceof User ? $user : null;
    }

    public static function companyId(?Authenticatable $actor = null): ?int
    {
        $actor ??= static::actor();

        return match (true) {
            $actor instanceof Admin => static::homeCompanyId(),
            $actor instanceof User => $actor->company_id ? (int) $actor->company_id : null,
            default => null,
        };
    }

    public static function homeCompanyId(): ?int
    {
        $id = Company::query()->orderBy('id')->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @return array{0: class-string, 1: int}
     */
    public static function actorKey(?Authenticatable $actor = null): array
    {
        $actor ??= static::actor();
        abort_if($actor === null, 403);

        return [$actor::class, (int) $actor->getAuthIdentifier()];
    }
}
