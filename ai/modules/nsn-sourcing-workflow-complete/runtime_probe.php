<?php

use Illuminate\Contracts\Console\Kernel;

// Phase-1 research only: show non-secret effective settings, no database writes.
require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$connection = config('database.default');
echo json_encode([
    'php' => PHP_VERSION,
    'laravel' => $app->version(),
    'database_driver' => config('database.connections.'.$connection.'.driver'),
    'queue_driver' => config('queue.default'),
    'queue_retry_after' => config('queue.connections.'.config('queue.default').'.retry_after'),
    'memory_limit' => ini_get('memory_limit'),
    'zip_available' => extension_loaded('zip'),
    'pdo_drivers' => PDO::getAvailableDrivers(),
], JSON_PRETTY_PRINT).PHP_EOL;
