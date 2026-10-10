<?php

use App\Services\PubLog\PubLogCsv;

it('rejects malformed quoting and bounds individual record size', function (string $input) {
    $stream = fopen('php://temp', 'w+');
    fwrite($stream, $input);
    rewind($stream);
    try {
        expect(fn () => (new PubLogCsv)->read($stream))->toThrow(RuntimeException::class);
    } finally {
        fclose($stream);
    }
})->with(['"value"x,"two"', '"unterminated', '"nul'.chr(0).'"', str_repeat('x', 65537)]);

it('accepts BOM headers and source columns in a different order', function () {
    $stream = fopen('php://temp', 'w+');
    fwrite($stream, "\xEF\xBB\xBF".'"B","A"'."\r\n");
    rewind($stream);
    expect((new PubLogCsv)->header($stream, ['A', 'B']))->toBe(['B', 'A']);
    fclose($stream);
});

it('rejects invalid UTF-8 instead of replacing source characters', function () {
    expect(fn () => (new PubLogCsv)->row([chr(0x96)], ['X'], 'UTF-8'))->toThrow(RuntimeException::class);
    expect(fn () => (new PubLogCsv)->row([chr(0x81)], ['X'], 'Windows-1252'))->toThrow(RuntimeException::class);
});
