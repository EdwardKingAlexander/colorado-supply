<?php

namespace App\Services\Sam;

use App\Models\SamCageLookup;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Company names and locations for CAGE codes, from the SAM.gov Entity
 * Management API, cached locally (ai/modules/dibbs-rfq-tracking, Phase 8).
 *
 * Verified 2026-10-04:
 * - the API answers on sam.gov/api/prod (api.sam.gov 404s);
 * - `cageCode` takes up to 100 codes joined with `~` (commas return nothing);
 * - results come back 10 per page (`page`, `size=10`), so 100 codes can take
 *   up to 10 requests. The first version read only page 0 and recorded 90 of
 *   100 real companies as "not found".
 * Personal keys without a SAM role get 10 entity requests a day, so lookups
 * are cached for 30 days and only made on demand.
 */
class CageDirectory
{
    public const BATCH_SIZE = 100;

    public const PAGE_SIZE = 10;

    public const REFRESH_AFTER_DAYS = 30;

    /**
     * Look up any of these CAGE codes not cached recently.
     *
     * @param  array<int, string>  $cages
     * @return array{requested: int, requests: int, found: int}
     *
     * @throws RuntimeException when SAM.gov refuses or fails (what was found is still saved)
     */
    public function lookup(array $cages): array
    {
        $cages = array_values(array_unique(array_map(fn ($c) => strtoupper(trim($c)), array_filter($cages))));

        $fresh = SamCageLookup::whereIn('cage_code', $cages)
            ->where('fetched_at', '>=', now()->subDays(self::REFRESH_AFTER_DAYS))
            ->pluck('cage_code')
            ->all();

        $todo = array_values(array_diff($cages, $fresh));
        $requests = 0;
        $found = 0;

        foreach (array_chunk($todo, self::BATCH_SIZE) as $batch) {
            ['entities' => $entities, 'requests' => $made, 'error' => $error] = $this->fetch($batch);
            $requests += $made;
            $found += count($entities);

            foreach ($batch as $cage) {
                $entity = $entities[$cage] ?? null;

                // "Not found" is only known once every page was read.
                if ($entity === null && $error !== null) {
                    continue;
                }

                SamCageLookup::updateOrCreate(['cage_code' => $cage], [
                    'found' => $entity !== null,
                    'legal_business_name' => $entity['legal_business_name'] ?? null,
                    'dba_name' => $entity['dba_name'] ?? null,
                    'city' => $entity['city'] ?? null,
                    'state' => $entity['state'] ?? null,
                    'country' => $entity['country'] ?? null,
                    'uei' => $entity['uei'] ?? null,
                    'registration_status' => $entity['registration_status'] ?? null,
                    'fetched_at' => now(),
                ]);
            }

            if ($error !== null) {
                Log::warning('CAGE lookup stopped early', ['error' => $error, 'found' => $found, 'requests' => $requests]);

                throw new RuntimeException($error.($found ? " ({$found} found so far were saved.)" : ''));
            }
        }

        Log::info('CAGE lookup', ['requested' => count($cages), 'fetched' => count($todo), 'requests' => $requests, 'found' => $found]);

        return ['requested' => count($todo), 'requests' => $requests, 'found' => $found];
    }

    /**
     * Every page of results for up to BATCH_SIZE codes.
     *
     * @param  array<int, string>  $cages
     * @return array{entities: array<string, array<string, ?string>>, requests: int, error: ?string}
     */
    protected function fetch(array $cages): array
    {
        $entities = [];
        $requests = 0;
        $maxPages = (int) ceil(count($cages) / self::PAGE_SIZE) + 1; // safety bound

        for ($page = 0; $page < $maxPages; $page++) {
            $response = Http::timeout(30)->acceptJson()->get(config('services.sam.entity_url'), [
                'api_key' => config('services.sam.api_key'),
                'cageCode' => implode('~', $cages),
                'page' => $page,
                'size' => self::PAGE_SIZE,
            ]);
            $requests++;

            if ($response->status() === 429) {
                return ['entities' => $entities, 'requests' => $requests, 'error' => 'SAM.gov entity lookup limit reached for today (personal keys without a SAM role get 10 requests/day). Try again tomorrow.'];
            }

            if (! $response->successful()) {
                return ['entities' => $entities, 'requests' => $requests, 'error' => "SAM.gov entity lookup failed (HTTP {$response->status()})."];
            }

            $rows = $response->json('entityData') ?? [];

            foreach ($rows as $entity) {
                $registration = $entity['entityRegistration'] ?? [];
                $address = $entity['coreData']['physicalAddress'] ?? [];
                $cage = strtoupper((string) ($registration['cageCode'] ?? ''));

                if ($cage !== '') {
                    $entities[$cage] = [
                        'legal_business_name' => $registration['legalBusinessName'] ?? null,
                        'dba_name' => $registration['dbaName'] ?? null,
                        'city' => $address['city'] ?? null,
                        'state' => $address['stateOrProvinceCode'] ?? null,
                        'country' => $address['countryCode'] ?? null,
                        'uei' => $registration['ueiSAM'] ?? null,
                        'registration_status' => $registration['registrationStatus'] ?? null,
                    ];
                }
            }

            $total = (int) ($response->json('totalRecords') ?? 0);

            if ($rows === [] || ($page + 1) * self::PAGE_SIZE >= $total) {
                break;
            }
        }

        return ['entities' => $entities, 'requests' => $requests, 'error' => null];
    }
}
