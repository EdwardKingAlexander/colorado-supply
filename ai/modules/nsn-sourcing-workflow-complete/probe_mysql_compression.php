<?php

// Research-only alteration of the disposable sample; does not change app migrations.
$pdo = new PDO('mysql:host=127.0.0.1;port=13316;dbname=publog_sample', 'root', '');
$report = [];
foreach (['nsn_catalog_items', 'nsn_references', 'cage_entities', 'nsn_catalog_relationships', 'pub_log_definitions'] as $table) {
    $start = microtime(true);
    $pdo->exec('ALTER TABLE '.$table.' ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8');
    $pdo->query('ANALYZE TABLE '.$table)->fetchAll();
    $report[$table] = ['rebuild_seconds' => microtime(true) - $start, 'count' => $pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn()];
    echo json_encode([$table => $report[$table]]).PHP_EOL;
}
$report['sizes'] = $pdo->query("SELECT TABLE_NAME, DATA_LENGTH, INDEX_LENGTH, ROW_FORMAT FROM information_schema.TABLES WHERE TABLE_SCHEMA='publog_sample'")->fetchAll(PDO::FETCH_ASSOC);
file_put_contents(__DIR__.'/mysql-compression-probe.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
