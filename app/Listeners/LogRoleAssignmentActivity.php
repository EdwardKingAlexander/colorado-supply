<?php

namespace App\Listeners;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;

/**
 * Bridge Spatie Permission's role/permission events into the activity log,
 * answering "who was granted what role, when" (audit-logging phase 2).
 * Spatie's packages do not bridge this automatically, and the events only
 * fire when `permission.events_enabled` is true.
 */
class LogRoleAssignmentActivity
{
    public function handle(RoleAttached|RoleDetached|PermissionAttached|PermissionDetached $event): void
    {
        [$kind, $eventName, $verb, $preposition] = match (true) {
            $event instanceof RoleAttached => ['role', 'role_assigned', 'Assigned', 'to'],
            $event instanceof RoleDetached => ['role', 'role_removed', 'Removed', 'from'],
            $event instanceof PermissionAttached => ['permission', 'permission_granted', 'Granted', 'to'],
            default => ['permission', 'permission_revoked', 'Revoked', 'from'],
        };

        $payload = $kind === 'role' ? $event->rolesOrIds : $event->permissionsOrIds;
        $names = $this->resolveNames($payload, $kind);

        if ($names === []) {
            return;
        }

        $subject = $event->model;
        $kindWord = count($names) > 1 ? $kind.'s' : $kind;
        $quoted = collect($names)->map(fn (string $name) => "'{$name}'")->implode(', ');
        $description = "{$verb} {$kindWord} {$quoted} {$preposition} ".class_basename($subject)." #{$subject->getKey()}";

        $companyId = $subject instanceof User ? $subject->company_id : null;

        activity()
            ->useLog($this->logNameFor($subject))
            ->performedOn($subject)
            ->event($eventName)
            ->withProperties([
                'type' => $kind,
                'action' => $eventName,
                'names' => $names,
                'company_id' => $companyId,
            ])
            ->tap(fn (Activity $activity) => $activity->company_id = $companyId)
            ->log($description);
    }

    private function logNameFor(Model $subject): string
    {
        return match (true) {
            $subject instanceof User => 'users',
            $subject instanceof Admin => 'admins',
            default => 'default',
        };
    }

    /**
     * Normalize the event payload (ids, names, models, or a mix) to names.
     *
     * @return list<string>
     */
    private function resolveNames(mixed $rolesOrIds, string $kind): array
    {
        $items = $rolesOrIds instanceof Collection
            ? $rolesOrIds
            : collect(is_array($rolesOrIds) ? $rolesOrIds : [$rolesOrIds]);

        $names = [];
        $ids = [];

        foreach ($items as $item) {
            if ($item instanceof Model) {
                $names[] = $item->getAttribute('name') ?? (string) $item->getKey();
            } elseif ($item instanceof \BackedEnum) {
                $names[] = (string) $item->value;
            } elseif (is_numeric($item)) {
                $ids[] = (int) $item;
            } else {
                $names[] = (string) $item;
            }
        }

        if ($ids !== []) {
            $lookup = $kind === 'role' ? app(Role::class) : app(Permission::class);
            $names = array_merge($names, $lookup->newQuery()->whereKey($ids)->pluck('name')->all());
        }

        return array_values(array_unique($names));
    }
}
