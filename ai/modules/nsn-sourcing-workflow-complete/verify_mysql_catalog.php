<?php

use App\Services\PubLog\PubLogCatalog;
use App\Services\PubLog\PubLogImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\PubLogFixtures;

$root = dirname(__DIR__, 3);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'publog_verify', 'database.connections.publog_verify' => ['driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 13316, 'database' => 'publog_verify', 'username' => 'root', 'password' => '', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_bin', 'strict' => true, 'prefix' => ''], 'cache.default' => 'array', 'app.debug' => false, 'nsn_lookup.directory' => __DIR__.'/artifacts/mysql-benchmark/fixture-imports']);
$pdo = new PDO('mysql:host=127.0.0.1;port=13316', 'root', '');
$pdo->exec('CREATE DATABASE IF NOT EXISTS publog_verify CHARACTER SET utf8mb4 COLLATE utf8mb4_bin');
if (Schema::hasTable('pub_log_imports')) {
    throw new RuntimeException('Fixture database already exists; preserve prior verification.');
}
(require $root.'/database/migrations/2026_10_05_100000_create_pub_log_catalog_tables.php')->up();
$directory = __DIR__.'/artifacts/mysql-benchmark/fixture-source';
$first = PubLogFixtures::ready($directory);
$catalog = app(PubLogCatalog::class);
$catalog->activate($first->id, null);
$second = PubLogFixtures::ready($directory, '2026-10-02');
$catalog->activate($second->id, $first->id);
$catalog->activate($first->id, $second->id, true);
$again = app(PubLogImporter::class)->prepare($directory, '2026-10-01');
if ($catalog->activeId() !== $first->id || $first->id !== $again->id || DB::table('nsn_references')->where('import_id', $first->id)->where('niin', '000000045')->count() !== 2) {
    throw new RuntimeException('MySQL import/activation/idempotency regression.');
}
$pdo->exec('USE publog_sample');
$queries = [
    'nsn' => "SELECT * FROM nsn_catalog_items WHERE import_id=1 AND nsn='5940000000045' LIMIT 25",
    'niin' => "SELECT * FROM nsn_references WHERE import_id=1 AND niin='000000045' ORDER BY id LIMIT 25",
    'part' => "SELECT * FROM nsn_references WHERE import_id=1 AND part_search_key='BHMA-A156.4' ORDER BY id LIMIT 25",
    'cage' => "SELECT * FROM nsn_references WHERE import_id=1 AND cage_code='24039' ORDER BY id LIMIT 25",
];
$results = [];
foreach ($queries as $name => $sql) {
    $times = [];
    for ($i = 0; $i < 100; $i++) {
        $start = hrtime(true);
        $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $times[] = (hrtime(true) - $start) / 1e6;
    }
    sort($times);
    $results[$name] = ['p95_ms' => $times[94], 'explain' => $pdo->query('EXPLAIN '.$sql)->fetchAll(PDO::FETCH_ASSOC)];
}
$report = ['mysql_fixture_import_activation_rollback_idempotency' => 'passed', 'first_validation' => $first->validation, 'sample_lookup_only' => $results];
file_put_contents(__DIR__.'/mysql-verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode($report, JSON_PRETTY_PRINT).PHP_EOL;
