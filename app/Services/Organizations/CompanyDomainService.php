<?php

namespace App\Services\Organizations;

use App\Models\Admin;
use App\Models\Company;
use App\Models\CompanyEmailDomain;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompanyDomainService
{
    public function normalize(string $domain): string
    {
        $domain = Str::lower(trim($domain));

        if (str_starts_with($domain, '@')) {
            $domain = substr($domain, 1);
        }

        $domain = rtrim($domain, '.');

        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

            if ($ascii !== false) {
                $domain = Str::lower($ascii);
            }
        }

        if (! $this->isValidDomain($domain)) {
            throw ValidationException::withMessages([
                'domain' => 'Enter a valid email domain without @, a URL, path, or port.',
            ]);
        }

        return $domain;
    }

    public function emailDomain(string $email): ?string
    {
        $email = Str::lower(trim($email));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        $domain = Str::afterLast($email, '@');

        try {
            return $this->normalize($domain);
        } catch (ValidationException) {
            return null;
        }
    }

    public function matches(Company $company, string $email): bool
    {
        $domain = $this->emailDomain($email);

        if ($domain === null) {
            return false;
        }

        return $company->emailDomains()->where('domain', $domain)->exists();
    }

    public function assertMembershipAllowed(?Company $company, string $email): void
    {
        if ($company === null || $company->domain_enforcement_enabled_at === null) {
            return;
        }

        if (! $this->matches($company, $email)) {
            throw ValidationException::withMessages([
                'email' => "The email address must use an approved domain for {$company->name}.",
            ]);
        }
    }

    /**
     * @return Collection<int, User>
     */
    public function mismatchedUsers(Company $company): Collection
    {
        $domains = $company->emailDomains()->pluck('domain')->flip();

        return $company->users()
            ->get()
            ->filter(function ($user) use ($domains) {
                $domain = $this->emailDomain($user->email);

                return $domain === null || ! $domains->has($domain);
            })
            ->values();
    }

    public function activate(Company $company, ?Admin $admin = null): Company
    {
        return DB::transaction(function () use ($company, $admin) {
            $lockedCompany = Company::query()->lockForUpdate()->findOrFail($company->getKey());

            if (! $lockedCompany->emailDomains()->exists()) {
                throw ValidationException::withMessages([
                    'domains' => 'Add at least one approved email domain before enabling enforcement.',
                ]);
            }

            $mismatches = $this->mismatchedUsers($lockedCompany);

            if ($mismatches->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'domains' => 'Resolve mismatched company members before enabling enforcement: '.$mismatches->pluck('email')->join(', '),
                ]);
            }

            if ($lockedCompany->domain_enforcement_enabled_at === null) {
                $lockedCompany->forceFill(['domain_enforcement_enabled_at' => now()])->save();

                $activity = activity('organization')->performedOn($lockedCompany);

                if ($admin) {
                    $activity->causedBy($admin);
                }

                $activity
                    ->event('domain_enforcement_activated')
                    ->withProperties([
                        'company_id' => $lockedCompany->getKey(),
                        'attributes' => ['domain_enforcement_enabled_at' => $lockedCompany->domain_enforcement_enabled_at?->toISOString()],
                        'old' => ['domain_enforcement_enabled_at' => null],
                    ])
                    ->log('Company email-domain enforcement activated');
            }

            return $lockedCompany->refresh();
        });
    }

    public function deactivate(Company $company, ?Admin $admin = null): Company
    {
        return DB::transaction(function () use ($company, $admin) {
            $lockedCompany = Company::query()->lockForUpdate()->findOrFail($company->getKey());
            $oldValue = $lockedCompany->domain_enforcement_enabled_at?->toISOString();

            if ($oldValue !== null) {
                $lockedCompany->forceFill(['domain_enforcement_enabled_at' => null])->save();

                $activity = activity('organization')->performedOn($lockedCompany);

                if ($admin) {
                    $activity->causedBy($admin);
                }

                $activity
                    ->event('domain_enforcement_deactivated')
                    ->withProperties([
                        'company_id' => $lockedCompany->getKey(),
                        'attributes' => ['domain_enforcement_enabled_at' => null],
                        'old' => ['domain_enforcement_enabled_at' => $oldValue],
                    ])
                    ->log('Company email-domain enforcement deactivated');
            }

            return $lockedCompany->refresh();
        });
    }

    public function makePrimary(CompanyEmailDomain $emailDomain): void
    {
        DB::transaction(function () use ($emailDomain) {
            if (! $emailDomain->is_primary) {
                $emailDomain->update(['is_primary' => true]);
            }
        });
    }

    private function isValidDomain(string $domain): bool
    {
        if ($domain === '' || strlen($domain) > 253) {
            return false;
        }

        if (Str::contains($domain, ['://', '/', '\\', ':', '@', ' '])) {
            return false;
        }

        if (! str_contains($domain, '.')) {
            return false;
        }

        return (bool) preg_match(
            '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z](?:[a-z0-9-]{0,61}[a-z0-9])?$/i',
            $domain,
        );
    }
}
