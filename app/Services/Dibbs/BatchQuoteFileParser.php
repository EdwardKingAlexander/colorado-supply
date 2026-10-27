<?php

namespace App\Services\Dibbs;

/**
 * Parses DIBBS batch quote (bq*.txt) and approved source (as*.txt) files.
 *
 * Both are comma-delimited with double-quote separators, CRLF, ISO-8859-1.
 * Batch quote rows are kept verbatim: the Phase 6 export fills vendor columns
 * into DLA's own pre-filled rows rather than rebuilding them. Column numbers
 * below are DLA's 1-based numbers from BatchFormat.aspx.
 */
class BatchQuoteFileParser
{
    /** Columns per row in DLA's bq files (verified 2026-10-04). */
    public const BQ_COLUMNS = 121;

    public const AS_COLUMNS = 4;

    protected const MAX_BAD_LINE_RATIO = 0.1;

    /**
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, string>}
     *
     * @throws DibbsLayoutException
     */
    public function parseBatchQuotes(string $contents): array
    {
        $rows = [];
        $occurrences = [];

        $result = $this->parseCsv($contents, self::BQ_COLUMNS, 'batch quote', function (array $cols, string $line) use (&$rows, &$occurrences) {
            $key = $cols[0].'|'.$cols[45];
            $occurrences[$key] = ($occurrences[$key] ?? -1) + 1;

            $rows[] = [
                'solicitation_number' => $cols[0],        // col 1
                'purchase_request' => $cols[45],          // col 46
                'row_index' => $occurrences[$key],
                'line_number' => $cols[43] ?: null,       // col 44
                'quantity' => ctype_digit($cols[48]) ? (int) $cols[48] : null,      // col 49
                'delivery_days' => ctype_digit($cols[50]) ? (int) $cols[50] : null, // col 51
                'raw_row' => $line,
            ];
        });

        return ['rows' => $rows, 'errors' => $result];
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, string>}
     *
     * @throws DibbsLayoutException
     */
    public function parseApprovedSources(string $contents): array
    {
        $rows = [];

        $errors = $this->parseCsv($contents, self::AS_COLUMNS, 'approved source', function (array $cols) use (&$rows) {
            $rows[] = [
                'nsn' => $cols[0],
                'cage_code' => $cols[1],
                'part_number' => $cols[2],
                'company_name' => $cols[3] !== '' ? $cols[3] : null,
            ];
        });

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * Split one DLA row into fields.
     *
     * DLA's format is "comma delimited, double quote separated": every field is
     * wrapped in quotes and fields are separated by `","`. Quotes inside values
     * are NOT escaped (e.g. part number `RK 800M4-T 1-1/2"`), which breaks a
     * standard CSV reader, so split on the separator itself.
     *
     * @return array<int, string>
     */
    public static function splitRow(string $line): array
    {
        $line = trim($line);

        if (str_starts_with($line, '"') && str_ends_with($line, '"') && strlen($line) >= 2) {
            $line = substr($line, 1, -1);
        }

        return explode('","', $line);
    }

    /**
     * @return array<int, string> row errors
     */
    protected function parseCsv(string $contents, int $expectedColumns, string $label, callable $onRow): array
    {
        $contents = mb_convert_encoding($contents, 'UTF-8', 'ISO-8859-1');
        $lines = array_values(array_filter(preg_split('/\r\n|\n|\r/', $contents), fn ($l) => trim($l) !== ''));

        if ($lines === []) {
            return [];
        }

        $errors = [];
        $bad = 0;

        foreach ($lines as $i => $line) {
            $cols = array_map('trim', self::splitRow($line));

            if (count($cols) !== $expectedColumns) {
                $bad++;
                $errors[] = 'Line '.($i + 1).": expected {$expectedColumns} columns, got ".count($cols).'.';

                continue;
            }

            if ($cols[0] === '') {
                $errors[] = 'Line '.($i + 1).': first column is empty.';

                continue;
            }

            $onRow($cols, $line);
        }

        if ($bad / count($lines) > self::MAX_BAD_LINE_RATIO) {
            throw new DibbsLayoutException(sprintf(
                'DIBBS %s layout mismatch: %d of %d lines do not have %d columns. DLA may have changed the format.',
                $label, $bad, count($lines), $expectedColumns,
            ));
        }

        return $errors;
    }
}
