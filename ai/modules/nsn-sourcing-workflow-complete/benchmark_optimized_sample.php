<?php

use App\Models\PubLogImport;
use App\Services\PubLog\PubLogCsv;
use App\Services\PubLog\PubLogSchema;
use App\Services\PubLog\PubLogWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__, 3);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'publog_optimized_sample', 'database.connections.publog_optimized_sample' => ['driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 13316, 'database' => 'publog_optimized_sample', 'username' => 'root', 'password' => '', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_bin', 'strict' => true, 'prefix' => ''], 'cache.default' => 'array', 'app.debug' => false]);
$pdo = new PDO('mysql:host=127.0.0.1;port=13316', 'root', '');
$pdo->exec('CREATE DATABASE IF NOT EXISTS publog_optimized_sample CHARACTER SET utf8mb4 COLLATE utf8mb4_bin');
if (Schema::hasTable('pub_log_imports')) {
    throw new RuntimeException('Sample already exists; preserve evidence and use existing report.');
}
(require $root.'/database/migrations/2026_10_05_100000_create_pub_log_catalog_tables.php')->up();
DB::disableQueryLog();
$import = PubLogImport::create(['release_date' => '2026-10-01', 'fingerprint' => hash('sha256', 'sample'), 'status' => 'sample_only', 'manifest' => []]);
$csv = app(PubLogCsv::class);
$writer = app(PubLogWriter::class);
$report = ['mysql' => $pdo->query('SELECT VERSION()')->fetchColumn(), 'sample_limit_per_member' => 100000, 'members' => []];
try {
    foreach (PubLogSchema::members() as $member => $schema) {
        $zip = new ZipArchive;
        $zip->open(__DIR__.'/artifacts/2026-10/'.$schema['archive'].'.zip');
        $stream = $zip->getStream($member);
        $header = $csv->header($stream, $schema['fields']);
        $start = microtime(true);
        $rows = $stored = 0;
        while ($rows < 100000) {
            $batch = [];
            for ($i = 0; $i < 500 && ($values = $csv->read($stream)) !== false; $i++) {
                $batch[] = [++$rows, $csv->row($values, $header, $schema['encoding'])];
            }
            if (! $batch) {
                break;
            }
            $stored += DB::transaction(fn () => $writer->write($import->id, $member, $batch));
        }
        fclose($stream);
        $zip->close();
        $report['members'][$member] = ['rows' => $rows, 'stored' => $stored, 'seconds' => microtime(true) - $start];
        echo json_encode([$member => $report['members'][$member]]).PHP_EOL;
    }
} finally {
    foreach (array_unique(array_column(PubLogSchema::members(), 'table')) as $table) {
        DB::statement('ANALYZE TABLE '.$table);
    }
    $report['peak_php_bytes'] = memory_get_peak_usage(true);
    $report['table_sizes'] = DB::select("SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'publog_optimized_sample'");
    file_put_contents(__DIR__.'/mysql-optimized-sample.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
}

