<?php

// Only the isolated local benchmark. Keeps its existing resumable rows.
$pdo = new PDO('mysql:host=127.0.0.1;port=13316;dbname=publog_benchmark', 'root', '');
foreach (['nsn_catalog_items', 'nsn_references', 'cage_entities', 'nsn_catalog_relationships', 'pub_log_definitions'] as $table) {
    $pdo->exec('ALTER TABLE '.$table.' ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8');
    echo $table.PHP_EOL;
}
