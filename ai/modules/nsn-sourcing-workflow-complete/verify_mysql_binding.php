<?php

$pdo = new PDO('mysql:host=127.0.0.1;port=13316;dbname=publog_benchmark', 'root', '');
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$report = [];
foreach (['integer' => PDO::PARAM_INT, 'string' => PDO::PARAM_STR] as $label => $type) {
    $query = $pdo->prepare('EXPLAIN SELECT cage_code, summary_hash FROM cage_entities WHERE import_id=1 AND cage_code IN (?,?,?)');
    foreach (['10000', '10001', '10002'] as $i => $code) {
        $query->bindValue($i + 1, $type === PDO::PARAM_INT ? (int) $code : $code, $type);
    }
    $query->execute();
    $report[$label] = $query->fetchAll(PDO::FETCH_ASSOC);
}
file_put_contents(__DIR__.'/mysql-binding-explain.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode($report, JSON_PRETTY_PRINT).PHP_EOL;
