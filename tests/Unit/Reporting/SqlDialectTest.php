<?php

use App\Reporting\DateBucket;
use App\Reporting\Sql\MySqlDialect;
use App\Reporting\Sql\SqliteDialect;

test('mysql date buckets', function (DateBucket $bucket, string $sql) {
    expect((new MySqlDialect)->dateBucket('o.created_at', $bucket))->toBe($sql);
})->with([
    'day' => [DateBucket::Day, "DATE_FORMAT(o.created_at, '%Y-%m-%d')"],
    'week' => [DateBucket::Week, "DATE_FORMAT(DATE_SUB(o.created_at, INTERVAL WEEKDAY(o.created_at) DAY), '%Y-%m-%d')"],
    'month' => [DateBucket::Month, "DATE_FORMAT(o.created_at, '%Y-%m')"],
    'quarter' => [DateBucket::Quarter, "CONCAT(YEAR(o.created_at), '-Q', QUARTER(o.created_at))"],
    'year' => [DateBucket::Year, "DATE_FORMAT(o.created_at, '%Y')"],
]);

test('sqlite date buckets', function (DateBucket $bucket, string $sql) {
    expect((new SqliteDialect)->dateBucket('o.created_at', $bucket))->toBe($sql);
})->with([
    'day' => [DateBucket::Day, 'date(o.created_at)'],
    'week' => [DateBucket::Week, "date(o.created_at, 'weekday 0', '-6 days')"],
    'month' => [DateBucket::Month, "strftime('%Y-%m', o.created_at)"],
    'quarter' => [DateBucket::Quarter, "(strftime('%Y', o.created_at) || '-Q' || ((CAST(strftime('%m', o.created_at) AS INTEGER) + 2) / 3))"],
    'year' => [DateBucket::Year, "strftime('%Y', o.created_at)"],
]);

test('sqlite buckets evaluate to the expected keys, including the ISO week year rollover', function (string $date, array $expected) {
    $pdo = new PDO('sqlite::memory:');
    $dialect = new SqliteDialect;

    foreach ($expected as $bucket => $value) {
        $sql = 'SELECT '.$dialect->dateBucket('?', DateBucket::from($bucket));
        $statement = $pdo->prepare($sql);
        $statement->execute(array_fill(0, substr_count($sql, '?'), $date));

        expect((string) $statement->fetchColumn())->toBe($value, "{$bucket} of {$date}");
    }
})->with([
    'sunday ends the previous ISO week' => ['2026-01-04 18:00:00', ['day' => '2026-01-04', 'week' => '2025-12-29', 'month' => '2026-01', 'quarter' => '2026-Q1', 'year' => '2026']],
    'monday starts its own week' => ['2025-12-29 00:00:00', ['week' => '2025-12-29', 'quarter' => '2025-Q4']],
    'leap day' => ['2028-02-29 12:00:00', ['day' => '2028-02-29', 'week' => '2028-02-28', 'month' => '2028-02']],
    'quarter edges' => ['2026-09-30 23:59:59', ['quarter' => '2026-Q3']],
]);

test('day differences', function () {
    expect((new MySqlDialect)->dateDiffDays('sh.delivered_at', 'sh.shipped_at'))->toBe('DATEDIFF(sh.delivered_at, sh.shipped_at)')
        ->and((new SqliteDialect)->dateDiffDays('sh.delivered_at', 'sh.shipped_at'))
        ->toBe('CAST(julianday(date(sh.delivered_at)) - julianday(date(sh.shipped_at)) AS INTEGER)');
});

test('numeric placeholders', function () {
    expect((new MySqlDialect)->numericPlaceholder())->toBe('?')
        ->and((new SqliteDialect)->numericPlaceholder())->toBe('(? + 0)');
});
