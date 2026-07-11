<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class Location extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'slug',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id')->withTrashed();
    }

    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    public function childrenWithTrashed(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id')->withTrashed();
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'location_products');
    }

    public function locationProducts(): HasMany
    {
        return $this->hasMany(LocationProduct::class);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->parent
            ? "{$this->parent->name} / {$this->name}"
            : $this->name;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('organization')
            ->logOnly(['company_id', 'parent_id', 'name', 'slug', 'deleted_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->company_id = $this->company_id;
        $activity->properties = $activity->properties
            ->put('company_id', $this->company_id)
            ->when(auth('admin')->check(), fn ($properties) => $properties->put('administrator_override', true));
    }

    protected static function booted(): void
    {
        static::saving(function (Location $location) {
            if ($location->parent_id === null) {
                return;
            }

            if ($location->exists && $location->parent_id === $location->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'A location cannot be its own parent.',
                ]);
            }

            $parent = Location::query()->find($location->parent_id);

            if (! $parent) {
                throw ValidationException::withMessages([
                    'parent_id' => 'The selected parent location does not exist.',
                ]);
            }

            if ((int) $parent->company_id !== (int) $location->company_id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'A sublocation must belong to the same company as its parent location.',
                ]);
            }

            $ancestor = $parent;
            $visited = [];

            while ($ancestor !== null) {
                if ($location->exists && (int) $ancestor->id === (int) $location->id) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'A location cannot be moved beneath itself or one of its sublocations.',
                    ]);
                }

                if (isset($visited[$ancestor->id])) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'The selected location hierarchy contains a cycle.',
                    ]);
                }

                $visited[$ancestor->id] = true;
                $ancestor = $ancestor->parent_id
                    ? Location::query()->find($ancestor->parent_id)
                    : null;
            }
        });

        static::deleting(function (Location $location) {
            if (! $location->isForceDeleting() && $location->children()->exists()) {
                throw ValidationException::withMessages([
                    'child_strategy' => 'Choose whether to promote sublocations or archive the entire subtree.',
                ]);
            }
        });
    }
}
