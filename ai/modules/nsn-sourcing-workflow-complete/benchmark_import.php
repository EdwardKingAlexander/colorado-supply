<?php

// Isolated, local-only benchmark. Never reads the application's database connection.
use App\Models\PubLogImport;
use App\Services\PubLog\PubLogImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__, 3);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config([
    'database.default' => 'publog_benchmark',
    'database.connections.publog_benchmark' => ['driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 13316, 'database' => 'publog_benchmark', 'username' => 'root', 'password' => '', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_bin', 'strict' => true, 'prefix' => ''],
    'cache.default' => 'array', 'app.debug' => false,
    'nsn_lookup.directory' => __DIR__.'/artifacts/mysql-benchmark/imports',
]);
$pdo = new PDO('mysql:host=127.0.0.1;port=13316', 'root', '');
$pdo->exec('CREATE DATABASE IF NOT EXISTS publog_benchmark CHARACTER SET utf8mb4 COLLATE utf8mb4_bin');
if (! Schema::hasTable('pub_log_imports')) {
    (require $root.'/database/migrations/2026_10_05_100000_create_pub_log_catalog_tables.php')->up();
}
DB::disableQueryLog();
$start = microtime(true);
$service = app(PubLogImporter::class);
$import = PubLogImport::first() ?? $service->prepare(__DIR__.'/artifacts/2026-10', '2026-10-01');
$last = 0;
try {
    $service->run($import, (int) ($argv[1] ?? 300), function ($member, $progress, $status) use (&$last, $start) {
        if (microtime(true) - $last < 10 && $status === 'importing') {
            return;
        }
        $last = microtime(true);
        echo json_encode(['member' => $member, 'progress' => $progress, 'status' => $status, 'seconds' => round($last - $start, 2), 'peak_bytes' => memory_get_peak_usage(true)]).PHP_EOL;
    });
} finally {
    $report = ['mysql' => $pdo->query('SELECT VERSION()')->fetchColumn(), 'seconds' => microtime(true) - $start, 'peak_php_bytes' => memory_get_peak_usage(true), 'import' => $import->fresh()->toArray(), 'table_sizes' => DB::select("SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'publog_benchmark'")];
    file_put_contents(__DIR__.'/mysql-import-benchmark.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
}
