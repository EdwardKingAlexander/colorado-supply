<?php

// Upgrade only the disposable local benchmark. The populated item schema is unchanged.
$pdo = new PDO('mysql:host=127.0.0.1;port=13316;dbname=publog_benchmark', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
foreach (['nsn_references', 'nsn_catalog_relationships'] as $table) {
    if ((int) $pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn() !== 0) {
        throw new RuntimeException('Refuse to replace populated benchmark columns.');
    }
}
$pdo->exec('ALTER TABLE nsn_references DROP COLUMN codes, MODIFY row_hash BINARY(32) NOT NULL, '.implode(', ', array_map(fn ($f) => 'ADD COLUMN '.$f.' VARCHAR(255) NOT NULL', ['cage_status','rncc','rnvc','dac','rnaac','rnfc','rnsc','rnjc','sadc','hcc','msds','medals'])));
$pdo->exec('ALTER TABLE nsn_catalog_relationships DROP COLUMN data, MODIFY row_hash BINARY(32) NOT NULL, '.implode(', ', array_map(fn ($f) => 'ADD COLUMN '.$f.' VARCHAR(255) NULL', ['related_nsn','isc','orig_stdzn_dec','dt_stdzn_dec','niin_stat_cd','cancelled_niin_fsc','cancelled_niin','eff_date','demil'])));
foreach (['nsn_catalog_items', 'nsn_references', 'cage_entities', 'nsn_catalog_relationships', 'pub_log_definitions'] as $table) {
    $pdo->exec('ALTER TABLE '.$table.' ROW_FORMAT=DYNAMIC KEY_BLOCK_SIZE=0');
    echo $table.' ready, uncompressed'.PHP_EOL;
}
