<?php

namespace App\Services\Dibbs;

use Carbon\Carbon;

/**
 * Parses a DIBBS daily RFQ index file (inYYMMDD.txt).
 *
 * Layout from DLA's /Rfq/RfqFileDefs.aspx (verified 2026-10-04): fixed width,
 * 140 bytes per record, CRLF, ISO-8859-1. Fields are sliced by byte offset
 * BEFORE converting to UTF-8, because a converted accented character would
 * shift every later field.
 */
class InFileParser
{
    public const RECORD_LENGTH = 140;

    /** [field => width], in file order. */
    public const LAYOUT = [
        'solicitation_number' => 13,
        'nsn' => 46,
        'purchase_request' => 13,
        'return_by' => 8,
        'document_file' => 19,
        'quantity' => 7,
        'unit_of_issue' => 2,
        'nomenclature' => 21,
        'buyer_code' => 5,
        'amsc' => 1,
        'item_type' => 1,
        'set_aside' => 1,
        'set_aside_percent' => 3,
    ];

    /** Share of malformed lines above which the whole file is rejected as a layout change. */
    protected const MAX_BAD_LINE_RATIO = 0.1;

    /**
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, string>}
     *
     * @throws DibbsLayoutException
     */
    public function parse(string $contents): array
    {
        $lines = array_values(array_filter(preg_split('/\r\n|\n|\r/', $contents), fn ($l) => trim($l) !== ''));

        if ($lines === []) {
            throw new DibbsLayoutException('RFQ index file is empty.');
        }

        $rows = [];
        $errors = [];
        $badLength = 0;

        foreach ($lines as $i => $line) {
            $lineNo = $i + 1;

            if (strlen($line) !== self::RECORD_LENGTH) {
                $badLength++;
                $errors[] = "Line {$lineNo}: expected ".self::RECORD_LENGTH.' characters, got '.strlen($line).'.';

                continue;
            }

            $fields = $this->slice($line);
            $problem = $this->validate($fields);

            if ($problem !== null) {
                $errors[] = "Line {$lineNo}: {$problem}";

                continue;
            }

            $rows[] = $this->toRow($fields, $line);
        }

        if ($badLength / count($lines) > self::MAX_BAD_LINE_RATIO) {
            throw new DibbsLayoutException(sprintf(
                'RFQ index layout mismatch: %d of %d lines are not %d characters. DLA may have changed the format (see /Rfq/RfqFileDefs.aspx).',
                $badLength, count($lines), self::RECORD_LENGTH,
            ));
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * @return array<string, string>
     */
    protected function slice(string $line): array
    {
        $fields = [];
        $offset = 0;

        foreach (self::LAYOUT as $name => $width) {
            $fields[$name] = trim(mb_convert_encoding(substr($line, $offset, $width), 'UTF-8', 'ISO-8859-1'));
            $offset += $width;
        }

        return $fields;
    }

    protected function validate(array $f): ?string
    {
        if (strlen($f['solicitation_number']) !== 13) {
            return 'solicitation number must be 13 characters.';
        }

        if ($f['purchase_request'] === '') {
            return 'purchase request is missing.';
        }

        // 4-digit FSC + 9-character NIIN. The NIIN is usually digits, but DLA also
        // issues local stock numbers with letters (e.g. 5330LN0035636, 9535LLNCA9756),
        // 74 of 10,961 lines over 2026-09-25..10-02. Those are real RFQs.
        if ($f['item_type'] === '1' && ! preg_match('/^\d{4}[0-9A-Z]{9}$/', $f['nsn'])) {
            return "NSN '{$f['nsn']}' is not a 4-digit FSC plus 9-character NIIN.";
        }

        if ($f['return_by'] !== '' && ! $this->parseDate($f['return_by'])) {
            return "return-by date '{$f['return_by']}' is not MM/DD/YY.";
        }

        if ($f['quantity'] !== '' && ! ctype_digit($f['quantity'])) {
            return "quantity '{$f['quantity']}' is not a number.";
        }

        return null;
    }

    protected function toRow(array $f, string $line): array
    {
        $isNsn = $f['item_type'] === '1';

        return [
            'solicitation_number' => $f['solicitation_number'],
            'purchase_request' => $f['purchase_request'],
            'nsn' => $f['nsn'],
            'item_type' => $f['item_type'],
            'fsc' => $isNsn ? substr($f['nsn'], 0, 4) : null,
            'niin' => $isNsn ? substr($f['nsn'], 4) : null,
            'nomenclature' => $f['nomenclature'] ?: null,
            'quantity' => $f['quantity'] !== '' ? (int) $f['quantity'] : null,
            'unit_of_issue' => $f['unit_of_issue'] ?: null,
            'return_by_date' => $f['return_by'] !== '' ? $this->parseDate($f['return_by'])?->toDateString() : null,
            'document_file' => $f['document_file'] ?: null,
            'buyer_code' => $f['buyer_code'] ?: null,
            'amsc' => $f['amsc'] ?: null,
            'set_aside' => $f['set_aside'] ?: null,
            'set_aside_percent' => ctype_digit($f['set_aside_percent']) ? (int) $f['set_aside_percent'] : null,
            // 9th character T or U: eligible for automated award (DLA batch format spec).
            'automated_award' => in_array(substr($f['solicitation_number'], 8, 1), ['T', 'U'], true),
            'raw_record' => mb_convert_encoding($line, 'UTF-8', 'ISO-8859-1'),
        ];
    }

    protected function parseDate(string $value): ?Carbon
    {
        $date = Carbon::createFromFormat('!m/d/y', $value);

        return $date && $date->format('m/d/y') === $value ? $date : null;
    }
}
