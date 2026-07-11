<?php

namespace App\Rules;

use App\Models\Company;
use App\Services\Organizations\CompanyDomainService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MatchesCompanyEmailDomain implements ValidationRule
{
    public function __construct(private readonly ?Company $company) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->company === null || $this->company->domain_enforcement_enabled_at === null) {
            return;
        }

        if (! app(CompanyDomainService::class)->matches($this->company, (string) $value)) {
            $fail("The {$attribute} must use an approved domain for {$this->company->name}.");
        }
    }
}
