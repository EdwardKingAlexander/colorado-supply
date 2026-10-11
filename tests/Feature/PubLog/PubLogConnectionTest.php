<?php

use App\Models\NsnReference;
use App\Models\PubLogImport;
use App\Services\NsnLookupService;
use App\Services\PubLog\PubLogCatalog;
use App\Services\PubLog\PubLogDb;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\Support\PubLogFixtures;

beforeEach(function () {
    $this->directory = sys_get_temp_dir().'/publog-connection-'.bin2hex(random_bytes(8));
    config([
        'database.connections.publog_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true],
        'nsn_lookup.connection' => 'publog_test',
        'nsn_lookup.directory' => $this->directory.'/owned',
        'nsn_lookup.minimum_free_bytes' => 0,
    ]);
    $this->artisan('migrate', ['--database' => 'publog_test', '--path' => 'database/migrations/2026_10_05_100000_create_pub_log_catalog_tables.php'])->assertSuccessful();
    $this->travelTo(now()->setDate(2026, 10, 9));
});

afterEach(function () {
    File::deleteDirectory($this->directory);
    DB::purge('publog_test');
});

it('imports, activates and looks up the catalog on its own connection', function () {
    $import = PubLogFixtures::ready($this->directory.'/source');
    app(PubLogCatalog::class)->activate($import->id, null);

    expect(PubLogDb::connection()->getName())->toBe('publog_test')
        ->and((new NsnReference)->getConnectionName())->toBe('publog_test')
        ->and(PubLogDb::connection()->table('pub_log_catalog_state')->value('active_import_id'))->toBe($import->id)
        ->and(PubLogImport::count())->toBe(1)
        ->and(DB::connection()->table('pub_log_imports')->count())->toBe(0)
        ->and(DB::connection()->table('nsn_references')->count())->toBe(0)
        ->and(app(NsnLookupService::class)->lookup('nsn', '5940-00-000-0045')['status'])->toBe('match');
});

it('uses the default connection when none is configured', function () {
    config(['nsn_lookup.connection' => null]);

    expect(PubLogDb::name())->toBeNull()
        ->and(PubLogDb::connection()->getName())->toBe(DB::getDefaultConnection())
        ->and((new NsnReference)->getConnectionName())->toBeNull();
});

it('never recreates or drops tables in a separately configured catalog database', function () {
    $migration = require base_path('database/migrations/2026_10_05_100000_create_pub_log_catalog_tables.php');
    PubLogDb::connection()->table('pub_log_catalog_state')->where('id', 1)->update(['check_status' => 'kept']);
    $default = DB::getDefaultConnection();
    DB::setDefaultConnection('publog_test');
    try {
        $migration->up();
        $migration->down();
    } finally {
        DB::setDefaultConnection($default);
    }

    expect(PubLogDb::connection()->getSchemaBuilder()->hasTable('nsn_references'))->toBeTrue()
        ->and(PubLogDb::connection()->table('pub_log_catalog_state')->value('check_status'))->toBe('kept');
});
