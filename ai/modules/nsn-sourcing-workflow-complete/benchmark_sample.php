<?php

// Disposable research database only; no Laravel connection or application tables.
$base = __DIR__.'/artifacts/2026-10/';
$output = [];
$sources = [
    ['REFERENCE', 'V_FLIS_PART.CSV', ['NIIN', 'PART_NUMBER', 'CAGE_CODE']],
    ['IDENTIFICATION', 'P_FLIS_NSN.CSV', ['NIIN', 'FSC', 'ITEM_NAME']],
    ['CAGE', 'P_CAGE.CSV', ['CAGE_CODE', 'COMPANY', 'CAGE_STATUS']],
    ['CAGE', 'V_CAGE_ADDRESS.CSV', ['CAGE_CODE', 'COMPANY_NAME', 'CITY']],
];
foreach ($sources as [$archive, $member, $keys]) {
    $started = microtime(true);
    $path = $base.$member.'.sample.sqlite';
    if (file_exists($path)) {
        throw new RuntimeException('Research output already exists: '.$path);
    }
    $pdo = new PDO('sqlite:'.$path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE records (id INTEGER PRIMARY KEY, version_id INTEGER NOT NULL, k1 TEXT, k2 TEXT, k3 TEXT, source_row INTEGER, raw TEXT)');
    $statement = $pdo->prepare('INSERT INTO records(version_id,k1,k2,k3,source_row,raw) VALUES(1,?,?,?,?,?)');
    $zip = new ZipArchive;
    if ($zip->open($base.$archive.'.zip') !== true) {
        throw new RuntimeException('Cannot open archive');
    }
    $stream = $zip->getStream($member);
    $headers = fgetcsv($stream, null, ',', '"', '');
    $count = 0;
    $samples = [];
    $pdo->beginTransaction();
    while ($count < 100000 && ($values = fgetcsv($stream, null, ',', '"', '')) !== false) {
        $row = array_combine($headers, $values);
        $count++;
        $statement->execute([$row[$keys[0]], $row[$keys[1]], $row[$keys[2]], $count, json_encode($row, JSON_THROW_ON_ERROR)]);
        if ($count % 1000 === 0) {
            $samples[] = $row[$keys[0]];
        }
        if ($count % 500 === 0) {
            $pdo->commit();
            $pdo->beginTransaction();
        }
    }
    $pdo->commit();
    fclose($stream);
    $zip->close();
    foreach (['k1', 'k2', 'k3'] as $key) {
        $pdo->exec('CREATE INDEX idx_'.$key.' ON records(version_id,'.$key.',id)');
    }
    $importSeconds = microtime(true) - $started;
    $query = $pdo->prepare('SELECT * FROM records WHERE version_id=1 AND k1=? ORDER BY id LIMIT 25');
    $timings = [];
    foreach ($samples as $sample) {
        $queryStart = hrtime(true);
        $query->execute([$sample]);
        $query->fetchAll(PDO::FETCH_ASSOC);
        $timings[] = (hrtime(true) - $queryStart) / 1e6;
    }
    sort($timings);
    clearstatcache(true, $path);
    $result = [
        'file' => $member,
        'rows' => $count,
        'seconds_including_indexes' => round($importSeconds, 3),
        'database_bytes' => filesize($path),
        'bytes_per_row_with_raw_json_and_three_indexes' => filesize($path) / $count,
        'php_process_peak_bytes' => memory_get_peak_usage(true),
        'warm_exact_lookup_p95_ms' => $timings[(int) ceil(count($timings) * .95) - 1],
        'explain' => $pdo->query("EXPLAIN QUERY PLAN SELECT * FROM records WHERE version_id=1 AND k1='000000042' ORDER BY id LIMIT 25")->fetchAll(PDO::FETCH_ASSOC),
    ];
    $output[] = $result;
    echo json_encode($result, JSON_PRETTY_PRINT).PHP_EOL;
    $pdo = null;
}
file_put_contents(__DIR__.'/sample-benchmark.json', json_encode($output, JSON_PRETTY_PRINT).PHP_EOL);
