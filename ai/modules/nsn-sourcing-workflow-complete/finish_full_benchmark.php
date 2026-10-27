<?php

// Local benchmark evidence only; never activates a release or uses app credentials.
$pdo = new PDO('mysql:host=127.0.0.1;port=13316;dbname=publog_benchmark', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$import = $pdo->query('SELECT id, status, progress, validation, error FROM pub_log_imports ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
$report = ['finished_at' => gmdate('c'), 'import' => $import, 'production_capacity_approved' => false];
if ($import['status'] === 'ready') {
    foreach (['nsn_catalog_items', 'nsn_references', 'cage_entities', 'nsn_catalog_relationships', 'pub_log_definitions'] as $table) {
        $pdo->query('ANALYZE TABLE '.$table)->fetchAll();
    }
    $report['tables'] = $pdo->query("SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH, DATA_FREE, ROW_FORMAT FROM information_schema.TABLES WHERE TABLE_SCHEMA='publog_benchmark'")->fetchAll(PDO::FETCH_ASSOC);
    $report['allocated_table_bytes'] = array_sum(array_map(fn ($t) => $t['DATA_LENGTH'] + $t['INDEX_LENGTH'] + $t['DATA_FREE'], $report['tables']));
    $report['two_snapshots_bytes'] = 2 * $report['allocated_table_bytes'];
    $report['limitations'] = 'Local benchmark; production free disk, binlog/temporary space, retained versions and concurrent app workload still require preflight. No production import is authorized by this report.';
    $queries = [
        'nsn' => "SELECT * FROM nsn_catalog_items WHERE import_id=1 AND nsn='5940000000045' LIMIT 25",
        'niin' => "SELECT * FROM nsn_references WHERE import_id=1 AND niin='000000045' ORDER BY id LIMIT 25",
        'part' => "SELECT * FROM nsn_references WHERE import_id=1 AND part_search_key='BHMA-A156.4' ORDER BY id LIMIT 25",
        'cage' => "SELECT * FROM nsn_references WHERE import_id=1 AND cage_code='24039' ORDER BY id LIMIT 25",
    ];
    foreach ($queries as $name => $sql) {
        $times = [];
        for ($i = 0; $i < 100; $i++) {
            $start = hrtime(true);
            $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $times[] = (hrtime(true) - $start) / 1e6;
        }
        sort($times);
        $report['lookups'][$name] = ['p95_ms' => $times[94], 'explain' => $pdo->query('EXPLAIN '.$sql)->fetchAll(PDO::FETCH_ASSOC)];
    }
}
file_put_contents(__DIR__.'/mysql-full-final-report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo 'Final report saved; import status: '.$import['status'].PHP_EOL;
