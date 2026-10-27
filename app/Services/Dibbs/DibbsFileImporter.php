<?php

namespace App\Services\Dibbs;

use App\Models\DibbsApprovedSource;
use App\Models\DibbsFile;
use App\Models\DibbsQuoteTemplate;
use App\Models\DibbsRfq;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Throwable;

/**
 * Registers and imports DIBBS daily files, from the scheduled download or a manual upload.
 *
 * Idempotent at two levels: a file is registered once per sha256, and rows are
 * upserted on DLA's natural keys (solicitation + purchase request; NSN + CAGE +
 * part number), so reissued RFQs update in place and keep their first-seen file.
 */
class DibbsFileImporter
{
    /** Row-level problems kept on the file record (the rest are counted only). */
    protected const MAX_STORED_ROW_ERRORS = 50;

    protected const CHUNK = 500;

    public function __construct(
        protected InFileParser $inParser = new InFileParser,
        protected BatchQuoteFileParser $bqParser = new BatchQuoteFileParser,
        protected BatchQuoteZip $zip = new BatchQuoteZip,
    ) {}

    /**
     * Store a file and record it, or return the existing record for identical content.
     *
     * @param  string  $name  DLA file name: inYYMMDD.txt or bqYYMMDD.zip
     *
     * @throws InvalidArgumentException for names that are not DIBBS daily files
     */
    public function register(string $contents, string $name, string $source): DibbsFile
    {
        [$type, $postingDate] = $this->classify($name);

        $sha = hash('sha256', $contents);

        if ($existing = DibbsFile::where('sha256', $sha)->first()) {
            return $existing;
        }

        $path = trim(config('dibbs.storage_directory', 'dibbs'), '/').'/'.substr($sha, 0, 12).'-'.strtolower(basename($name));
        Storage::disk('local')->put($path, $contents);

        return DibbsFile::create([
            'name' => strtolower(basename($name)),
            'type' => $type,
            'posting_date' => $postingDate,
            'source' => $source,
            'sha256' => $sha,
            'size_bytes' => strlen($contents),
            'stored_path' => $path,
            'status' => DibbsFile::STATUS_PENDING,
        ]);
    }

    /**
     * Import a registered file. Never throws: failures are recorded on the file
     * (status "failed" + error) for the caller to report.
     */
    public function import(DibbsFile $file): DibbsFile
    {
        if ($file->isImported()) {
            return $file;
        }

        try {
            $contents = Storage::disk('local')->get($file->stored_path);

            if ($contents === null) {
                throw new \RuntimeException("Stored file {$file->stored_path} is missing.");
            }

            [$rowCount, $errors] = DB::transaction(fn () => $file->type === DibbsFile::TYPE_INDEX
                ? $this->importIndex($file, $contents)
                : $this->importBatchQuotes($file, $contents));

            $this->linkQuoteTemplates();

            $file->update([
                'status' => DibbsFile::STATUS_IMPORTED,
                'row_count' => $rowCount,
                'error_count' => count($errors),
                'row_errors' => $errors === [] ? null : array_slice($errors, 0, self::MAX_STORED_ROW_ERRORS),
                'error' => null,
                'imported_at' => now(),
            ]);

            Log::info('DIBBS file imported', ['file' => $file->name, 'rows' => $rowCount, 'row_errors' => count($errors)]);
        } catch (Throwable $e) {
            $file->update([
                'status' => DibbsFile::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);

            Log::error('DIBBS file import failed', ['file' => $file->name, 'error' => $e->getMessage(), 'exception' => $e::class]);
        }

        return $file->refresh();
    }

    /**
     * @return array{0: int, 1: array<int, string>}
     */
    protected function importIndex(DibbsFile $file, string $contents): array
    {
        ['rows' => $rows, 'errors' => $errors] = $this->inParser->parse($contents);

        // Last occurrence wins if a key repeats within a file (none seen so far;
        // some databases reject an upsert that touches the same key twice).
        $rows = array_values(array_column(
            array_map(fn ($r) => $r + ['_key' => $r['solicitation_number'].'|'.$r['purchase_request']], $rows),
            null,
            '_key',
        ));
        $rows = array_map(fn ($r) => array_diff_key($r, ['_key' => true]), $rows);

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            $values = array_map(fn (array $row) => $row + [
                'first_seen_file_id' => $file->id,
                'last_seen_file_id' => $file->id,
            ], $chunk);

            DibbsRfq::upsert(
                $values,
                ['solicitation_number', 'purchase_request'],
                // first_seen_file_id is deliberately not updated: a reissue keeps when we first saw it.
                array_values(array_diff(array_keys($values[0]), ['solicitation_number', 'purchase_request', 'first_seen_file_id'])),
            );
        }

        return [count($rows), $errors];
    }

    /**
     * @return array{0: int, 1: array<int, string>}
     */
    protected function importBatchQuotes(DibbsFile $file, string $contents): array
    {
        ['bq' => $bq, 'as' => $as] = $this->zip->read($contents);

        ['rows' => $quoteRows, 'errors' => $quoteErrors] = $this->bqParser->parseBatchQuotes($bq);
        ['rows' => $sourceRows, 'errors' => $sourceErrors] = $as !== null
            ? $this->bqParser->parseApprovedSources($as)
            : ['rows' => [], 'errors' => []];

        foreach (array_chunk($quoteRows, self::CHUNK) as $chunk) {
            DibbsQuoteTemplate::upsert(
                array_map(fn (array $row) => $row + ['dibbs_file_id' => $file->id], $chunk),
                ['solicitation_number', 'purchase_request', 'row_index'],
                ['line_number', 'quantity', 'delivery_days', 'raw_row', 'dibbs_file_id'],
            );
        }

        foreach (array_chunk($this->uniqueSources($sourceRows), self::CHUNK) as $chunk) {
            DibbsApprovedSource::upsert(
                array_map(fn (array $row) => $row + ['dibbs_file_id' => $file->id], $chunk),
                ['nsn', 'cage_code', 'part_number'],
                ['company_name', 'dibbs_file_id'],
            );
        }

        $errors = array_merge(
            array_map(fn ($e) => "bq {$e}", $quoteErrors),
            array_map(fn ($e) => "as {$e}", $sourceErrors),
        );

        return [count($quoteRows) + count($sourceRows), $errors];
    }

    /**
     * Point batch quote rows at their RFQ. Runs after either file type, since
     * the bq zip and the index for a day can arrive in either order.
     */
    protected function linkQuoteTemplates(): void
    {
        DibbsQuoteTemplate::whereNull('dibbs_rfq_id')
            ->update(['dibbs_rfq_id' => DibbsRfq::select('id')
                ->whereColumn('dibbs_rfqs.solicitation_number', 'dibbs_quote_templates.solicitation_number')
                ->whereColumn('dibbs_rfqs.purchase_request', 'dibbs_quote_templates.purchase_request')
                ->limit(1)]);
    }

    /**
     * The same NSN/CAGE/part can repeat within one file; upsert rejects duplicate keys in one batch.
     */
    protected function uniqueSources(array $rows): array
    {
        $unique = [];

        foreach ($rows as $row) {
            $unique[$row['nsn'].'|'.$row['cage_code'].'|'.$row['part_number']] = $row;
        }

        return array_values($unique);
    }

    /**
     * @return array{0: string, 1: ?Carbon} [type, posting date]
     */
    public function classify(string $name): array
    {
        if (! preg_match('/^(in|bq)(\d{6})\.(txt|zip)$/i', basename($name), $m)
            || (strtolower($m[1]) === 'in') !== (strtolower($m[3]) === 'txt')) {
            throw new InvalidArgumentException("'{$name}' is not a DIBBS daily file. Expected inYYMMDD.txt or bqYYMMDD.zip.");
        }

        $date = Carbon::createFromFormat('!ymd', $m[2]);

        return [strtolower($m[1]), $date && $date->format('ymd') === $m[2] ? $date : null];
    }
}
