<?php

namespace App\Reporting;

use App\Models\Report;
use App\Models\User;
use App\Reporting\Definition\InvalidReportDefinition;
use App\Reporting\Definition\ReportDefinition;
use App\Reporting\Definition\ReportDefinitionValidator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Saving, sharing, copying and running report definitions for portal users.
 */
class ReportService
{
    public const MAX_SAVED_REPORTS = 200;

    private const PREVIEW_TTL_SECONDS = 60;

    public function __construct(
        private ReportDefinitionValidator $validator,
        private ReportRunner $runner,
    ) {}

    /**
     * Validate and run a definition (saved or not) for the preview, capped at
     * the preview row count. Identical previews are cached per user briefly.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function preview(User $user, mixed $definition): array
    {
        $validated = $this->validated($definition);
        $key = 'report-preview:'.$user->id.':'.sha1(json_encode($validated->toArray()));

        return Cache::remember($key, self::PREVIEW_TTL_SECONDS, fn () => $this->runner
            ->run($user, $validated, Limits::PREVIEW_ROWS)
            ->toArray());
    }

    /**
     * @return array<string, mixed>
     */
    public function run(User $user, Report $report): array
    {
        $result = $this->preview($user, $report->definition);
        $report->forceFill(['last_run_at' => now()])->saveQuietly();

        return $result;
    }

    /**
     * @param  array{name: string, description?: ?string, definition: mixed, visibility?: string, company_can_edit?: bool}  $attributes
     *
     * @throws ValidationException
     */
    public function create(User $user, array $attributes): Report
    {
        if (Report::query()->where('user_id', $user->id)->count() >= self::MAX_SAVED_REPORTS) {
            throw ValidationException::withMessages(['name' => 'You can save at most '.self::MAX_SAVED_REPORTS.' reports. Delete some first.']);
        }

        $definition = $this->validated($attributes['definition']);

        return Report::query()->create([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'name' => $attributes['name'],
            'description' => $attributes['description'] ?? null,
            ...$this->definitionAttributes($definition),
            ...$this->sharing($user, $attributes),
        ]);
    }

    /**
     * @param  array{name?: string, description?: ?string, definition?: mixed, visibility?: string, company_can_edit?: bool}  $attributes
     *
     * @throws ValidationException
     */
    public function update(User $user, Report $report, array $attributes): Report
    {
        $changes = array_intersect_key($attributes, array_flip(['name', 'description']));

        if (array_key_exists('definition', $attributes)) {
            $changes = [...$changes, ...$this->definitionAttributes($this->validated($attributes['definition']))];
        }

        if (array_key_exists('visibility', $attributes) || array_key_exists('company_can_edit', $attributes)) {
            Gate::forUser($user)->authorize('share', $report);
            // Shared with the owner's current company (they may have joined one since saving).
            $changes = [...$changes, 'company_id' => $user->company_id, ...$this->sharing($user, [
                'visibility' => $attributes['visibility'] ?? $report->visibility,
                'company_can_edit' => $attributes['company_can_edit'] ?? $report->company_can_edit,
            ])];
        }

        $report->update($changes);

        return $report;
    }

    public function duplicate(User $user, Report $report, ?string $name = null): Report
    {
        return $this->create($user, [
            'name' => $name ?? mb_substr("Copy of {$report->name}", 0, 120),
            'description' => $report->description,
            'definition' => $report->definition,
        ]);
    }

    /**
     * List entry for the library.
     *
     * @return array<string, mixed>
     */
    public function summary(User $user, Report $report): array
    {
        return [
            'id' => $report->id,
            'name' => $report->name,
            'description' => $report->description,
            'dataset' => $report->dataset,
            'mode' => $report->definition['mode'] ?? 'table',
            'visibility' => $report->visibility,
            'company_can_edit' => $report->company_can_edit,
            'owner' => ['id' => $report->user_id, 'name' => $report->owner?->name, 'is_me' => (int) $report->user_id === (int) $user->id],
            'last_run_at' => $report->last_run_at?->toIso8601String(),
            'updated_at' => $report->updated_at?->toIso8601String(),
            'permissions' => [
                'update' => $user->can('update', $report),
                'share' => $user->can('share', $report),
                'delete' => $user->can('delete', $report),
            ],
        ];
    }

    /**
     * Full report for the builder. A definition saved against fields that
     * have since changed comes back with its errors instead of failing.
     *
     * @return array<string, mixed>
     */
    public function detail(User $user, Report $report): array
    {
        return [
            ...$this->summary($user, $report),
            'definition' => $report->definition,
            'definition_errors' => (object) $this->validator->validate($report->definition),
        ];
    }

    /**
     * @throws ValidationException errors keyed definition.<json path>
     */
    public function validated(mixed $definition): ReportDefinition
    {
        try {
            return $this->validator->validated($definition);
        } catch (InvalidReportDefinition $exception) {
            $messages = [];

            foreach ($exception->errors() as $path => $message) {
                $messages["definition.{$path}"] = $message;
            }

            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @return array{dataset: string, definition: array<string, mixed>, schema_version: int}
     */
    private function definitionAttributes(ReportDefinition $definition): array
    {
        // Stored normalized: column keys filled in, empty sections dropped.
        return [
            'dataset' => $definition->dataset,
            'definition' => $definition->toArray(),
            'schema_version' => $definition->version,
        ];
    }

    /**
     * Company sharing needs a company; edit rights only mean something when shared.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{visibility: string, company_can_edit: bool}
     */
    private function sharing(User $user, array $attributes): array
    {
        $visibility = $attributes['visibility'] ?? Report::VISIBILITY_PRIVATE;

        if ($visibility === Report::VISIBILITY_COMPANY && ! $user->company_id) {
            throw ValidationException::withMessages(['visibility' => 'Join a company before sharing reports with it.']);
        }

        return [
            'visibility' => $visibility,
            'company_can_edit' => $visibility === Report::VISIBILITY_COMPANY && (bool) ($attributes['company_can_edit'] ?? false),
        ];
    }
}
