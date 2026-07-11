<?php

namespace App\Services\Organizations;

use App\Models\Company;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LocationManagementService
{
    public function create(Company $company, array $data): Location
    {
        return DB::transaction(function () use ($company, $data) {
            $parent = $this->resolveParent($company, $data['parent_id'] ?? null);

            return Location::query()->create([
                'company_id' => $company->getKey(),
                'parent_id' => $parent?->getKey(),
                'name' => trim($data['name']),
                'slug' => $this->uniqueSlug($company, $data['name']),
            ]);
        });
    }

    public function update(Location $location, array $data): Location
    {
        return DB::transaction(function () use ($location, $data) {
            $location = Location::query()->lockForUpdate()->findOrFail($location->getKey());
            $parent = $this->resolveParent($location->company, $data['parent_id'] ?? null);

            $this->assertNoCycle($location, $parent);

            $name = trim($data['name']);
            $attributes = [
                'parent_id' => $parent?->getKey(),
                'name' => $name,
            ];

            if ($name !== $location->name) {
                $attributes['slug'] = $this->uniqueSlug($location->company, $name, $location->getKey());
            }

            $location->update($attributes);

            return $location->refresh();
        });
    }

    public function archive(Location $location, ?string $childStrategy = null): void
    {
        DB::transaction(function () use ($location, $childStrategy) {
            $location = Location::query()->lockForUpdate()->findOrFail($location->getKey());
            $children = $location->children()->lockForUpdate()->get();

            if ($children->isNotEmpty() && ! in_array($childStrategy, ['promote', 'archive_subtree'], true)) {
                throw ValidationException::withMessages([
                    'child_strategy' => 'Choose whether to promote sublocations or archive the entire subtree.',
                ]);
            }

            if ($childStrategy === 'promote') {
                $children->each(fn (Location $child) => $child->update(['parent_id' => null]));
            } elseif ($childStrategy === 'archive_subtree') {
                $children->each(fn (Location $child) => $this->archiveDescendants($child));
            }

            $location->delete();
        });
    }

    public function restore(Location $location): Location
    {
        return DB::transaction(function () use ($location) {
            $location = Location::withTrashed()->lockForUpdate()->findOrFail($location->getKey());

            if (! $location->trashed()) {
                return $location;
            }

            if ($location->parent_id !== null
                && ! Location::query()->whereKey($location->parent_id)->exists()) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Restore the parent location first or move this sublocation after restoring it.',
                ]);
            }

            $location->restore();

            return $location->refresh();
        });
    }

    private function resolveParent(Company $company, int|string|null $parentId): ?Location
    {
        if ($parentId === null || $parentId === '') {
            return null;
        }

        $parent = Location::query()->find($parentId);

        if ($parent === null || (int) $parent->company_id !== (int) $company->getKey()) {
            throw ValidationException::withMessages([
                'parent_id' => 'The selected parent location must belong to your company.',
            ]);
        }

        return $parent;
    }

    private function assertNoCycle(Location $location, ?Location $parent): void
    {
        $ancestor = $parent;
        $visited = [];

        while ($ancestor !== null) {
            if ((int) $ancestor->getKey() === (int) $location->getKey()) {
                throw ValidationException::withMessages([
                    'parent_id' => 'A location cannot be moved beneath itself or one of its sublocations.',
                ]);
            }

            if (isset($visited[$ancestor->getKey()])) {
                throw ValidationException::withMessages([
                    'parent_id' => 'The selected location hierarchy contains a cycle.',
                ]);
            }

            $visited[$ancestor->getKey()] = true;
            $ancestor = $ancestor->parent_id
                ? Location::query()->find($ancestor->parent_id)
                : null;
        }
    }

    private function archiveDescendants(Location $location): void
    {
        $location->children()->lockForUpdate()->get()
            ->each(fn (Location $child) => $this->archiveDescendants($child));

        $location->delete();
    }

    private function uniqueSlug(Company $company, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'location';
        $slug = $base;
        $suffix = 2;

        while (Location::withTrashed()
            ->where('company_id', $company->getKey())
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
