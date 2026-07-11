<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Organizations\CompanyDomainService;
use Illuminate\Console\Command;

class AuditCompanyEmailDomains extends Command
{
    protected $signature = 'organizations:audit-email-domains {--json : Output machine-readable JSON}';

    protected $description = 'Report companies without approved domains and members whose emails do not match';

    public function handle(CompanyDomainService $domains): int
    {
        $rows = Company::query()
            ->with(['emailDomains:id,company_id,domain', 'users:id,company_id,email'])
            ->orderBy('name')
            ->get()
            ->map(function (Company $company) use ($domains) {
                $mismatches = $domains->mismatchedUsers($company)->pluck('email')->values();

                return [
                    'company_id' => $company->getKey(),
                    'company' => $company->name,
                    'domains' => $company->emailDomains->pluck('domain')->join(', '),
                    'enforcement' => $company->enforcesEmailDomains() ? 'enabled' : 'disabled',
                    'mismatched_users' => $mismatches->join(', '),
                    'ready' => $company->emailDomains->isNotEmpty() && $mismatches->isEmpty(),
                ];
            });

        if ($this->option('json')) {
            $this->line($rows->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(
                ['ID', 'Company', 'Domains', 'Enforcement', 'Mismatched users', 'Ready'],
                $rows->map(fn (array $row) => [
                    $row['company_id'],
                    $row['company'],
                    $row['domains'] ?: 'MISSING',
                    $row['enforcement'],
                    $row['mismatched_users'] ?: 'None',
                    $row['ready'] ? 'yes' : 'no',
                ]),
            );
        }

        $issues = $rows->where('ready', false)->count();

        if ($issues > 0) {
            $this->warn("{$issues} company domain configuration(s) require review.");

            return self::FAILURE;
        }

        $this->info('All company domain configurations are ready.');

        return self::SUCCESS;
    }
}
