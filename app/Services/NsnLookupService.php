<?php

namespace App\Services;

use App\Models\CageEntity;
use App\Models\MilSpecPart;
use App\Models\NsnCatalogItem;
use App\Models\NsnCatalogRelationship;
use App\Models\NsnReference;
use App\Models\PubLogDefinition;
use App\Models\PubLogImport;
use App\Services\PubLog\PubLogCatalog;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class NsnLookupService
{
    public const PAGE_SIZE = 20;

    public function normalize(string $type, string $value): string
    {
        $value = trim($value);
        $valid = match ($type) {
            'nsn' => preg_match('/^(?:[0-9]{13}|[0-9]{4}-[0-9]{2}-[0-9]{3}-[0-9]{4})$/D', $value),
            'niin' => preg_match('/^(?:[0-9]{9}|[0-9]{2}-[0-9]{3}-[0-9]{4})$/D', $value),
            'cage' => preg_match('/^[A-Za-z0-9]{5}$/D', $value),
            'part' => $value !== '' && mb_strlen($value) <= 255 && ! preg_match('/[\x00-\x1F\x7F]/', $value),
            default => false,
        };
        if (! $valid) {
            throw new InvalidArgumentException('Enter a valid NSN (13 digits), NIIN (9 digits), CAGE (5 letters or digits), or part number for the selected search type.');
        }

        return in_array($type, ['nsn', 'niin'], true) ? str_replace('-', '', $value) : mb_strtoupper($value, 'UTF-8');
    }

    /** Read-only, bounded results; every query in a response uses one release. */
    public function lookup(string $type, string $value, int $page = 1, ?int $snapshotId = null, int $relationshipPage = 1): array
    {
        $result = [
            'contract_version' => 2, 'success' => false, 'status' => 'invalid_input',
            'message' => '', 'error' => null, 'type' => $type, 'query' => $value,
            'nsn' => null, 'mil_spec_part_id' => null, 'saved' => false,
            'source' => null, 'items' => [], 'references' => [], 'companies' => [], 'relationships' => [],
            'page' => $page, 'has_more' => false, 'relationship_page' => $relationshipPage, 'relationships_have_more' => false,
        ];
        try {
            $query = $this->normalize($type, $value);
            if ($page < 1 || $page > 1000000 || $relationshipPage < 1 || $relationshipPage > 1000000) {
                throw new InvalidArgumentException('Invalid result page.');
            }
            $result['query'] = $query;
            $result['nsn'] = $type === 'nsn' ? $query : null;
        } catch (InvalidArgumentException $e) {
            return array_replace($result, ['message' => $e->getMessage(), 'error' => 'invalid_input']);
        }
        try {
            $snapshot = $snapshotId === null ? app(PubLogCatalog::class)->snapshot()
                : PubLogImport::whereKey($snapshotId)->where('status', 'ready')->whereNotNull('activated_at')->first();
            if (! $snapshot) {
                return array_replace($result, ['status' => 'catalog_unavailable', 'message' => 'No activated PUB LOG catalog is available. Ask an administrator to import and activate a validated release.', 'error' => 'catalog_unavailable']);
            }
            $id = $snapshot->id;
            $result['source'] = [
                'import_id' => $id, 'release_date' => $snapshot->release_date->format('Y-m-d'),
                'stale' => $snapshot->release_date->lt(now()->subDays(config('nsn_lookup.stale_after_days', 45))->startOfDay()),
                'warnings' => $snapshot->validation ?? [],
            ];
            $items = NsnCatalogItem::where('import_id', $id);
            if ($type === 'nsn' || $type === 'niin') {
                $items = $items->where($type, $query)->get();
            } else {
                $items = collect();
            }
            $refs = NsnReference::where('import_id', $id);
            if (in_array($type, ['nsn', 'niin'], true)) {
                $refs->whereIn('niin', $items->pluck('niin'));
            } else {
                $refs->where($type === 'part' ? 'part_search_key' : 'cage_code', $query);
            }
            $references = $refs->orderBy('id')->simplePaginate(self::PAGE_SIZE, ['*'], 'page', $page);
            if (! in_array($type, ['nsn', 'niin'], true)) {
                $items = NsnCatalogItem::where('import_id', $id)->whereIn('niin', $references->getCollection()->pluck('niin'))->get();
            }
            $cages = $references->getCollection()->pluck('cage_code')->all();
            if ($type === 'cage') {
                $cages[] = $query;
            }
            $companies = CageEntity::where('import_id', $id)->whereIn('cage_code', $cages)->get();
            $definitions = [];
            foreach ($references as $reference) {
                foreach ($reference->codes as $field => $code) {
                    if ($code !== '' && $code !== null) {
                        $definitions[] = $field.':'.$code;
                    }
                }
            }
            $meanings = PubLogDefinition::where('import_id', $id)->whereIn('code', array_unique($definitions))->get()->groupBy('code');
            $result['references'] = $references->getCollection()->map(function ($reference) use ($meanings) {
                $row = $reference->toArray();
                $row['code_meanings'] = [];
                foreach ($reference->codes as $field => $code) {
                    $row['code_meanings'][$field] = isset($meanings[$field.':'.$code])
                        ? $meanings[$field.':'.$code]->map(fn ($definition) => $definition->only(['literal', 'description']))->all() : [];
                }

                return $row;
            })->all();
            $nsns = $items->flatMap(fn ($item) => [$item->nsn, self::formatNsn($item->nsn)]);
            $curated = MilSpecPart::whereIn('nsn', $nsns)->get(['id', 'nsn'])->keyBy(fn ($part) => str_replace('-', '', $part->nsn));
            $result['items'] = $items->map(fn ($item) => [...$item->toArray(), 'formatted_nsn' => self::formatNsn($item->nsn), 'curated_id' => $curated[$item->nsn]->id ?? null])->all();
            $result['companies'] = $companies->toArray();
            $result['has_more'] = $references->hasMorePages();
            // Item context has its own pagination, so large status histories are never silently truncated.
            if (config('nsn_lookup.import_relationships') && in_array($type, ['nsn', 'niin'], true) && $items->isNotEmpty()) {
                $relationships = NsnCatalogRelationship::where('import_id', $id)->where('niin', $items->first()->niin)
                    ->orderBy('kind')->orderBy('id')->simplePaginate(self::PAGE_SIZE, ['*'], 'relationship_page', $relationshipPage);
                $result['relationships'] = $relationships->getCollection()->toArray();
                $result['relationships_have_more'] = $relationships->hasMorePages();
            }
            $matched = $items->isNotEmpty() || $references->isNotEmpty() || $companies->isNotEmpty();

            return array_replace($result, [
                'status' => $matched ? 'match' : 'no_match', 'success' => $matched,
                'message' => $matched ? 'PUB LOG catalog results. Current production and distributor status are not confirmed.'
                    : 'No matches on this page in this PUB LOG release. This does not establish that an NSN or part does not exist.',
            ]);
        } catch (Throwable $e) {
            Log::error('PUB LOG lookup failed', ['exception' => $e::class, 'message' => $e->getMessage()]);

            return array_replace($result, [
                'status' => 'operational_failure', 'error' => 'operational_failure',
                'message' => 'The catalog could not be queried. Try again; if it continues, ask an administrator to check the catalog service.',
                'items' => [], 'references' => [], 'companies' => [], 'relationships' => [],
                'has_more' => false, 'relationships_have_more' => false,
            ]);
        }
    }

    public static function formatNsn(string $nsn): string
    {
        return substr($nsn, 0, 4).'-'.substr($nsn, 4, 2).'-'.substr($nsn, 6, 3).'-'.substr($nsn, 9, 4);
    }
}
