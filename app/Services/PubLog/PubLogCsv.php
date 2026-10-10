<?php

namespace App\Services\PubLog;

use RuntimeException;

class PubLogCsv
{
    public function header($stream, array $expected): array
    {
        $fields = $this->read($stream);
        if ($fields) {
            $fields[0] = preg_replace('/^\xEF\xBB\xBF/', '', $fields[0]);
        }
        if (! $fields || count($fields) !== count(array_unique($fields))
            || array_diff($fields, $expected) || array_diff($expected, $fields)) {
            throw new RuntimeException('Unsupported CSV header; expected '.implode(', ', $expected).'.');
        }

        return $fields;
    }

    /** Bound record length as well as batch size, including embedded newlines. */
    public function read($stream): array|false
    {
        $firstRecord = ftell($stream) === 0;
        $line = '';
        $quoted = false;
        while (($piece = fgets($stream, 65537)) !== false) {
            $line .= $piece;
            if (strlen($line) > 65536) {
                throw new RuntimeException('CSV record exceeds the 64 KiB limit.');
            }
            // Escaped pairs toggle twice; odd quote count keeps the record open.
            if (substr_count($piece, '"') % 2 !== 0) {
                $quoted = ! $quoted;
            }
            if (! $quoted && (str_ends_with($piece, "\n") || feof($stream))) {
                break;
            }
        }
        if ($line === '') {
            return false;
        }
        if ($quoted) {
            throw new RuntimeException('Unterminated quoted CSV field.');
        }
        // fgetcsv/str_getcsv are permissive after closing quotes; validate CSV grammar.
        $record = rtrim($line, "\r\n");
        if ($firstRecord) {
            $record = preg_replace('/^\xEF\xBB\xBF/', '', $record);
        }
        if (! preg_match('/\\A(?:"(?:[^"\\x00]|"")*"|[^",\\r\\n\\x00]*)(?:,(?:"(?:[^"\\x00]|"")*"|[^",\\r\\n\\x00]*))*\\z/sD', $record)) {
            throw new RuntimeException('Malformed CSV quoting or NUL byte.');
        }

        return str_getcsv($record, ',', '"', '');
    }

    public function row(array $values, array $header, string $encoding): array
    {
        if (count($values) !== count($header) || in_array(null, $values, true)) {
            throw new RuntimeException('CSV row does not match its header.');
        }
        foreach ($values as &$value) {
            if ($encoding === 'Windows-1252') {
                if (preg_match('/[\x81\x8D\x8F\x90\x9D]/', $value)) {
                    throw new RuntimeException('Undefined Windows-1252 byte in CSV.');
                }
                $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
            } elseif (! mb_check_encoding($value, 'UTF-8')) {
                throw new RuntimeException('Invalid UTF-8; unsupported source encoding.');
            }
        }

        return array_combine($header, $values);
    }
}
