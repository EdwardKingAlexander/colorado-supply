<?php

namespace App\Reporting\Datasets;

use App\Models\User;
use App\Reporting\Fields\Field;
use App\Reporting\Fields\Join;
use App\Reporting\TenantScope;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * A reportable data source: a root table, the joins its fields may need, and
 * the tenant scope that is applied to every query built from it.
 */
abstract class Dataset
{
    /** @var array<string, Field>|null */
    private ?array $fieldsByKey = null;

    abstract public function key(): string;

    abstract public function label(): string;

    abstract public function description(): string;

    /** Root table alias; also the tie-break sort column's table. */
    abstract public function alias(): string;

    abstract protected function table(): string;

    /**
     * @return array<string, Join>
     */
    abstract protected function joins(): array;

    /**
     * @return list<Field>
     */
    abstract protected function defineFields(): array;

    /**
     * Restrict the query to rows the user may see, and drop soft-deleted rows.
     */
    abstract public function applyScope(Builder $query, User $user): void;

    /**
     * Joins added to every query, e.g. the parent row the tenant scope sits on.
     *
     * @return list<string>
     */
    protected function requiredJoins(): array
    {
        return [];
    }

    /**
     * @return array<string, Field>
     */
    public function fields(): array
    {
        if ($this->fieldsByKey === null) {
            $this->fieldsByKey = [];

            foreach ($this->defineFields() as $field) {
                if (isset($this->fieldsByKey[$field->key])) {
                    throw new LogicException("Duplicate report field [{$field->key}] in dataset [{$this->key()}].");
                }

                $this->fieldsByKey[$field->key] = $field;
            }
        }

        return $this->fieldsByKey;
    }

    public function field(string $key): ?Field
    {
        return $this->fields()[$key] ?? null;
    }

    public function newQuery(): Builder
    {
        return DB::table("{$this->table()} as {$this->alias()}");
    }

    /**
     * Add the required joins plus the given ones, dependencies first, each once.
     *
     * @param  list<string>  $keys
     */
    public function addJoins(Builder $query, array $keys): void
    {
        $joins = $this->joins();
        $added = [];

        $add = function (string $key) use (&$add, &$added, $joins, $query) {
            if (isset($added[$key])) {
                return;
            }

            $join = $joins[$key] ?? throw new LogicException("Unknown join [{$key}] in dataset [{$this->key()}].");

            foreach ($join->dependsOn as $dependency) {
                $add($dependency);
            }

            $added[$key] = true;
            $table = "{$join->table} as {$join->alias}";

            $join->inner
                ? $query->join($table, $join->first, '=', $join->second)
                : $query->leftJoin($table, $join->first, '=', $join->second);
        };

        foreach ([...$this->requiredJoins(), ...$keys] as $key) {
            $add($key);
        }
    }

    protected function scopeToOrders(Builder $query, User $user): void
    {
        TenantScope::apply($query, $user, 'o.company_id', 'o.portal_user_id');
        $query->whereNull('o.deleted_at');
    }

    protected function scopeToQuotes(Builder $query, User $user): void
    {
        TenantScope::apply($query, $user, 'q.company_id', 'q.portal_user_id');
        $query->whereNull('q.deleted_at');
    }
}
