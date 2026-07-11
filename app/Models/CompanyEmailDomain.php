<?php

namespace App\Models;

use App\Services\Organizations\CompanyDomainService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class CompanyEmailDomain extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'domain',
        'is_primary',
        'approved_by_admin_id',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('organization')
            ->logOnly(['domain', 'is_primary', 'approved_by_admin_id', 'approved_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->company_id = $this->company_id;
        $activity->properties = $activity->properties->put('company_id', $this->company_id);
    }

    protected static function booted(): void
    {
        static::creating(function (CompanyEmailDomain $emailDomain) {
            $emailDomain->domain = app(CompanyDomainService::class)->normalize($emailDomain->domain);
            $emailDomain->approved_by_admin_id ??= auth('admin')->id();
            $emailDomain->approved_at ??= now();

            if (! CompanyEmailDomain::query()->where('company_id', $emailDomain->company_id)->exists()) {
                $emailDomain->is_primary = true;
            }
        });

        static::updating(function (CompanyEmailDomain $emailDomain) {
            if ($emailDomain->isDirty('domain')) {
                $emailDomain->domain = app(CompanyDomainService::class)->normalize($emailDomain->domain);
            }

            if ($emailDomain->isDirty('is_primary') && ! $emailDomain->is_primary) {
                $hasOtherPrimary = CompanyEmailDomain::query()
                    ->where('company_id', $emailDomain->company_id)
                    ->whereKeyNot($emailDomain->getKey())
                    ->where('is_primary', true)
                    ->exists();

                if (! $hasOtherPrimary) {
                    throw ValidationException::withMessages([
                        'is_primary' => 'Each company must have a primary email domain.',
                    ]);
                }
            }
        });

        static::saved(function (CompanyEmailDomain $emailDomain) {
            if ($emailDomain->is_primary) {
                CompanyEmailDomain::query()
                    ->where('company_id', $emailDomain->company_id)
                    ->whereKeyNot($emailDomain->getKey())
                    ->where('is_primary', true)
                    ->get()
                    ->each(fn (CompanyEmailDomain $domain) => $domain->update(['is_primary' => false]));
            }
        });

        static::deleting(function (CompanyEmailDomain $emailDomain) {
            if ($emailDomain->company?->domain_enforcement_enabled_at !== null
                && $emailDomain->company->emailDomains()->count() <= 1) {
                throw ValidationException::withMessages([
                    'domain' => 'Disable domain enforcement before deleting the final approved domain.',
                ]);
            }
        });

        static::deleted(function (CompanyEmailDomain $emailDomain) {
            if ($emailDomain->is_primary) {
                $replacement = CompanyEmailDomain::query()
                    ->where('company_id', $emailDomain->company_id)
                    ->oldest('id')
                    ->first();

                if ($replacement) {
                    $replacement->update(['is_primary' => true]);
                }
            }
        });
    }
}
