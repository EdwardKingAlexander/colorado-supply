<?php

namespace App\Models;

use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A saved report-builder definition. Running it always applies the viewer's
 * own tenant scope, so sharing a report shares its layout, never its data.
 */
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory, SoftDeletes;

    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_COMPANY = 'company';

    protected $fillable = [
        'company_id',
        'user_id',
        'name',
        'description',
        'dataset',
        'definition',
        'schema_version',
        'visibility',
        'company_can_edit',
        'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'definition' => 'array',
            'schema_version' => 'integer',
            'company_can_edit' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isSharedWithCompany(): bool
    {
        return $this->visibility === self::VISIBILITY_COMPANY && $this->company_id !== null;
    }

    /**
     * Reports the user owns or that are shared with their company.
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->where(function (Builder $visible) use ($user) {
            $visible->where('user_id', $user->id);

            if ($user->company_id) {
                $visible->orWhere(fn (Builder $shared) => $shared
                    ->where('visibility', self::VISIBILITY_COMPANY)
                    ->where('company_id', $user->company_id));
            }
        });
    }
}
