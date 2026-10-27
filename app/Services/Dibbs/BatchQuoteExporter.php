<?php

namespace App\Services\Dibbs;

use App\Models\DibbsApprovedSource;
use App\Models\DibbsQuoteTemplate;
use App\Models\DibbsQuotingProfile;
use App\Models\DibbsRfqTracking;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Fills DLA's pre-filled batch quote rows with our quote and exports DIBBS
 * upload files (ai/modules/dibbs-rfq-tracking, Phase 6).
 *
 * Starts from DLA's own bq*.txt row (kept verbatim at import) and writes only
 * vendor columns; every RFQ-requirement column stays exactly as DLA sent it.
 * Column numbers are DLA's (1-based, BatchFormat.aspx, 30-APR-2024).
 *
 * Supported (v1): "bid without exception" (BI) on DLA's terms (FOB, inspection,
 * quantity, packaging), domestic end products, exact / superseding /
 * previously-approved parts, higher-level quality codes 6–8. Anything else is
 * reported as a problem and must be quoted in DIBBS directly.
 *
 * The app never submits: it produces files a person reviews and uploads.
 */
class BatchQuoteExporter
{
    /** DIBBS rejects batch files over 75 lines. */
    public const MAX_LINES_PER_FILE = 75;

    // DLA column numbers.
    protected const SOLICITATION = 1;

    protected const SET_ASIDE = 3;

    protected const QUOTER_CAGE = 6;

    protected const QUOTE_FOR_CAGE = 7;

    protected const SMALL_BUSINESS = 13;

    protected const JOINT_VENTURE = 18;

    protected const AFFIRMATIVE_ACTION = 21;

    protected const PREVIOUS_CONTRACTS = 22;

    protected const ADR = 23;

    protected const BID_TYPE = 24;

    protected const PACKAGING = 28;

    protected const UNIT_PRICE = 50;

    protected const DELIVERY_DAYS = 51;

    protected const HUBZONE_PREFERENCE = 57;

    protected const HAZARDOUS = 65;

    protected const MATERIAL = 67;

    protected const END_PRODUCT = 70;

    protected const COUNTRY_OF_ORIGIN = 71;

    protected const QUALIFYING_COUNTRY = 72;

    protected const MANUFACTURER_DEALER = 102;

    protected const MANUFACTURING_SOURCE = 103;

    protected const ITEM_DESCRIPTION = 105;

    protected const PART_OFFERED_CODE = 106;

    protected const PART_OFFERED_CAGE = 107;

    protected const PART_OFFERED_NUMBER = 108;

    protected const QUALITY_INDICATOR = 117;

    protected const QUALITY_CODE = 118;

    protected const CHILD_LABOR = 120;

    /** Part offered codes v1 supports (exact, superseding, previously approved). */
    public const SUPPORTED_PART_OFFERED = [
        '1' => '1 – Exact product (must be an approved CAGE + part number)',
        '3' => '3 – Superseding P/N, administrative change only',
        '4' => '4 – Superseding P/N, minor change / no configuration change',
        '5' => '5 – Previously approved product',
    ];

    /** Column 118 answers allowed for each column 117 requirement (1 and 2 unsupported). */
    public const QUALITY_CODES_FOR = [
        '8' => ['8'],
        '7' => ['8', '7'],
        '6' => ['8', '7', '6'],
    ];

    public const QUALITY_CODE_LABELS = [
        '8' => '8 – SAE AS9100',
        '7' => '7 – ISO 9001:2015',
        '6' => '6 – SAE AS9003 or ISO 9001 tailored to AS9003',
    ];

    /**
     * Everything that stops this quote from exporting, in plain language.
     *
     * @return array<int, string>
     */
    public function problems(DibbsRfqTracking $tracking, DibbsQuotingProfile $profile): array
    {
        $problems = [];
        $templates = $tracking->rfq?->quoteTemplates ?? collect();

        if (! $profile->isConfirmed()) {
            $problems[] = 'The quoting profile is incomplete or not confirmed.';
        }

        if ($templates->isEmpty()) {
            return [...$problems, 'No DLA batch quote row for this RFQ (import the day\'s bq file).'];
        }

        if ($tracking->unit_price === null) {
            $problems[] = 'Enter a unit price.';
        } elseif ((float) $tracking->unit_price < 0 || (float) $tracking->unit_price > 9999999.99999) {
            $problems[] = 'Unit price must be between 0 and 9,999,999.99999.';
        } elseif ((float) $tracking->unit_price == 0.0) {
            $problems[] = 'A zero price means "no charge" or "no bid" to DLA; quote that in DIBBS directly.';
        }

        $maxDelivery = str_starts_with($tracking->rfq->solicitation_number, 'SPM') ? 999 : 9999;
        if ($tracking->delivery_days === null || $tracking->delivery_days < 1 || $tracking->delivery_days > $maxDelivery) {
            $problems[] = "Enter delivery days (1–{$maxDelivery}).";
        }

        $dealer = $tracking->manufacturer_dealer ?? $profile->default_manufacturer_dealer;
        if (! array_key_exists((string) $dealer, DibbsQuotingProfile::MANUFACTURER_DEALER_CODES)) {
            $problems[] = 'Choose manufacturer or dealer.';
        } elseif (in_array($dealer, ['DD', 'QD'], true) && ! $this->validCage($tracking->manufacturing_source_cage)) {
            $problems[] = 'As a dealer, enter the CAGE of the facility that actually makes the item.';
        }

        if (! array_key_exists((string) ($tracking->hazardous_material ?? $profile->default_hazardous_material), DibbsQuotingProfile::HAZARDOUS_MATERIAL_CODES)) {
            $problems[] = 'Answer the hazardous material question.';
        }

        if (! array_key_exists((string) ($tracking->material_code ?? $profile->default_material_code), DibbsQuotingProfile::MATERIAL_CODES)) {
            $problems[] = 'Answer the material (new / used / surplus) question.';
        }

        foreach ($templates as $template) {
            $cols = $template->columns();
            $col = fn (int $n) => trim($cols[$n - 1] ?? '');

            if (count($cols) < self::CHILD_LABOR) {
                $problems[] = 'DLA\'s batch quote row has '.count($cols).' columns; expected at least '.self::CHILD_LABOR.'.';

                break;
            }

            if ($col(self::BID_TYPE) !== 'BI') {
                $problems[] = "DLA's row is not a plain bid (bid type '{$col(self::BID_TYPE)}'); quote in DIBBS.";
            }

            if ($col(self::HUBZONE_PREFERENCE) === 'Y' && in_array($profile->small_business_code, ['B', 'M'], true)) {
                $problems[] = 'HUBZone price preference applies (HUBZone waiver not supported); quote in DIBBS.';
            }

            if (in_array($col(self::ITEM_DESCRIPTION), ['P', 'B', 'N'], true)) {
                $problems = [...$problems, ...$this->partOfferedProblems($tracking, $col(self::ITEM_DESCRIPTION))];
            }

            $quality = $col(self::QUALITY_INDICATOR);
            if ($quality !== '' && $quality !== 'N') {
                $allowed = self::QUALITY_CODES_FOR[$quality] ?? null;

                if ($allowed === null) {
                    $problems[] = "Unknown higher-level quality requirement '{$quality}'; quote in DIBBS.";
                } elseif (! in_array((string) $tracking->quality_code, $allowed, true)) {
                    $problems[] = 'This item requires a higher-level quality system; choose the manufacturing facility\'s quality code ('.implode(', ', $allowed).').';
                }
            }
        }

        return array_values(array_unique($problems));
    }

    /**
     * @return array<int, string>
     */
    protected function partOfferedProblems(DibbsRfqTracking $tracking, string $itemDescription): array
    {
        // Numeric-string keys ('1', '3'…) are stored as ints by PHP; compare as strings.
        $allowed = $itemDescription === 'B' ? ['1'] : array_map('strval', array_keys(self::SUPPORTED_PART_OFFERED));

        if (! in_array((string) $tracking->part_offered_code, $allowed, true)) {
            return ['Approved-source item: choose the part offered ('.($itemDescription === 'B' ? 'exact product' : 'exact, superseding or previously approved').').'];
        }

        if (! $this->validCage($tracking->part_offered_cage) || blank($tracking->part_offered_number)) {
            return ['Enter the offered part\'s CAGE and part number.'];
        }

        if ($tracking->part_offered_code === '1') {
            $approved = DibbsApprovedSource::query()
                ->where('nsn', $tracking->rfq->nsn)
                ->whereRaw('upper(cage_code) = ?', [strtoupper($tracking->part_offered_cage)])
                ->whereRaw('upper(part_number) = ?', [strtoupper(trim($tracking->part_offered_number))])
                ->exists();

            if (! $approved) {
                return ['An exact product must match an approved CAGE + part number for this NSN.'];
            }
        }

        return [];
    }

    /**
     * Build upload files for ready quotes, mark them quoted, and snapshot what was sent.
     *
     * @param  Collection<int, DibbsRfqTracking>  $trackings
     * @return array{reference: string, lines: int, files: array<string, string>}
     *
     * @throws \InvalidArgumentException if any tracking has problems
     */
    public function export(Collection $trackings, DibbsQuotingProfile $profile, int $confirmedBy): array
    {
        $trackings = EloquentCollection::make($trackings->all())->loadMissing('rfq.quoteTemplates');

        foreach ($trackings as $tracking) {
            if ($problems = $this->problems($tracking, $profile)) {
                throw new \InvalidArgumentException($tracking->rfq->solicitation_number.': '.implode(' ', $problems));
            }
        }

        $reference = 'BQ-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4));
        $lines = [];
        $snapshots = [];

        foreach ($trackings as $tracking) {
            foreach ($tracking->rfq->quoteTemplates->sortBy('row_index') as $template) {
                $line = $this->fill($template, $tracking, $profile);
                $lines[] = $line;
                $snapshots[$tracking->id][] = $line;
            }
        }

        $chunks = array_chunk($lines, self::MAX_LINES_PER_FILE);
        $files = [];

        foreach ($chunks as $i => $chunk) {
            $name = strtolower($reference).(count($chunks) > 1 ? '-part'.($i + 1) : '').'.txt';
            $files[$name] = mb_convert_encoding(implode("\r\n", $chunk)."\r\n", 'ISO-8859-1', 'UTF-8');
        }

        DB::transaction(function () use ($trackings, $snapshots, $reference, $confirmedBy) {
            foreach ($trackings as $tracking) {
                $tracking->update([
                    'status' => 'quoted',
                    'quoted_at' => now(),
                    'exported_at' => now(),
                    'export_reference' => $reference,
                    'quote_snapshot' => ['lines' => $snapshots[$tracking->id], 'confirmed_by' => $confirmedBy],
                ]);
            }
        });

        return ['reference' => $reference, 'lines' => count($lines), 'files' => $files];
    }

    /**
     * A zip of several upload files (when a batch exceeds 75 lines).
     */
    public function zip(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dibbs-bq-export');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);

        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        $zip->close();
        $contents = file_get_contents($path);
        @unlink($path);

        return $contents;
    }

    /**
     * DLA's row with our vendor columns filled; all other columns untouched.
     */
    public function fill(DibbsQuoteTemplate $template, DibbsRfqTracking $tracking, DibbsQuotingProfile $profile): string
    {
        $cols = $template->columns();
        $set = function (int $n, ?string $value) use (&$cols) {
            $cols[$n - 1] = (string) $value;
        };
        $get = fn (int $n) => trim($cols[$n - 1] ?? '');

        $dealer = $tracking->manufacturer_dealer ?? $profile->default_manufacturer_dealer;

        $set(self::QUOTER_CAGE, strtoupper($profile->quoter_cage));
        $set(self::QUOTE_FOR_CAGE, strtoupper($profile->quote_for_cage));
        $set(self::SMALL_BUSINESS, $profile->small_business_code);
        $set(self::JOINT_VENTURE, ''); // not a joint venture
        $set(self::AFFIRMATIVE_ACTION, $profile->affirmative_action_code);
        $set(self::PREVIOUS_CONTRACTS, $profile->previous_contracts_code);
        $set(self::ADR, $profile->adr_code);
        $set(self::PACKAGING, 'Y'); // bid without exception meets DLA's packaging requirement
        $set(self::UNIT_PRICE, $this->formatPrice((string) $tracking->unit_price));
        $set(self::DELIVERY_DAYS, (string) $tracking->delivery_days);
        $set(self::HAZARDOUS, $tracking->hazardous_material ?? $profile->default_hazardous_material);
        $set(self::MATERIAL, $tracking->material_code ?? $profile->default_material_code);
        $set(self::END_PRODUCT, 'D'); // domestic end product (v1)
        $set(self::COUNTRY_OF_ORIGIN, '');
        $set(self::QUALIFYING_COUNTRY, '');
        $set(self::MANUFACTURER_DEALER, $dealer);
        $set(self::MANUFACTURING_SOURCE, in_array($dealer, ['DD', 'QD'], true) ? strtoupper($tracking->manufacturing_source_cage) : '');

        if (in_array($get(self::ITEM_DESCRIPTION), ['P', 'B', 'N'], true)) {
            $set(self::PART_OFFERED_CODE, $tracking->part_offered_code);
            $set(self::PART_OFFERED_CAGE, strtoupper($tracking->part_offered_cage));
            $set(self::PART_OFFERED_NUMBER, trim($tracking->part_offered_number));
        } else {
            $set(self::PART_OFFERED_CODE, '');
            $set(self::PART_OFFERED_CAGE, '');
            $set(self::PART_OFFERED_NUMBER, '');
        }

        $quality = $get(self::QUALITY_INDICATOR);
        $set(self::QUALITY_CODE, $quality !== '' && $quality !== 'N' ? $tracking->quality_code : '');

        $set(self::CHILD_LABOR, $profile->child_labor_code);

        // DLA's format: every field quoted, separated by "," (quotes inside values are not escaped).
        return '"'.implode('","', $cols).'"';
    }

    /**
     * Up to 5 decimals, at least 2, no thousands separators (e.g. 12.5 → "12.50", 0.12345 → "0.12345").
     */
    public function formatPrice(string $price): string
    {
        [$whole, $fraction] = explode('.', number_format((float) $price, 5, '.', ''));

        return $whole.'.'.str_pad(rtrim($fraction, '0'), 2, '0');
    }

    protected function validCage(?string $cage): bool
    {
        return (bool) preg_match('/^[0-9A-Za-z]{5}$/', (string) $cage);
    }
}
