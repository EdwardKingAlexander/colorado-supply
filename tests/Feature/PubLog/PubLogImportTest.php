<?php

use App\Models\CageEntity;
use App\Models\NsnCatalogItem;
use App\Models\NsnCatalogRelationship;
use App\Models\NsnReference;
use App\Models\PubLogImport;
use App\Services\PubLog\PubLogCatalog;
use App\Services\PubLog\PubLogImporter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\Support\PubLogFixtures;

beforeEach(function () {
    $this->directory = sys_get_temp_dir().'/publog-test-'.bin2hex(random_bytes(8));
    config(['nsn_lookup.directory' => $this->directory.'/owned', 'nsn_lookup.batch_size' => 2, 'nsn_lookup.minimum_free_bytes' => 0]);
    $this->travelTo(now()->setDate(2026, 10, 4));
});
afterEach(function () {
    File::deleteDirectory($this->directory);
});

it('imports real cross references and statuses without changing curated records or activating implicitly', function () {
    $legacy = DB::table('mil_spec_parts')->count();
    $import = PubLogFixtures::ready($this->directory.'/source');
    expect($import->status)->toBe('ready')
        ->and(app(PubLogCatalog::class)->activeId())->toBeNull()
        ->and(NsnCatalogItem::where('import_id', $import->id)->where('niin', '000000045')->value('nsn'))->toBe('5940000000045')
        ->and(NsnReference::where('import_id', $import->id)->where('niin', '000000045')->count())->toBe(2)
        ->and(NsnReference::where('part_search_key', 'BHMA-A156.4')->distinct()->count('niin'))->toBe(2)
        ->and(CageEntity::where('cage_code', '24039')->first()->summary['COMPANY'])->toBe('VARO LLC')
        ->and(CageEntity::where('cage_code', '0CEN3')->exists())->toBeTrue()
        ->and(DB::table('mil_spec_parts')->count())->toBe($legacy);
    expect(DB::table('nsn_catalog_relationships')->where('niin', '000000012')->value('niin_stat_cd'))->toBe('4');
    app(PubLogCatalog::class)->activate($import->id, null);
    expect(app(PubLogCatalog::class)->snapshot()->id)->toBe($import->id);
});

it('is idempotent and retains exact reference differences and original punctuation', function () {
    $source = PubLogFixtures::archives($this->directory.'/source', function ($files) {
        $lines = explode("\r\n", trim($files['V_FLIS_PART.CSV']));
        $files['V_FLIS_PART.CSV'] .= $lines[1]."\r\n";
        $different = str_replace('"3","2"', '"3","9"', $lines[1]);
        $files['V_FLIS_PART.CSV'] .= $different."\r\n";

        return $files;
    });
    $service = app(PubLogImporter::class);
    $first = $service->run($service->prepare($source, '2026-10-01'));
    $count = NsnReference::count();
    $repeat = $service->run($service->prepare($source, '2026-10-01'));
    expect($repeat->id)->toBe($first->id)->and(PubLogImport::count())->toBe(1)->and(NsnReference::count())->toBe($count);
    expect(NsnReference::where('niin', '000000042')->where('part_number', 'UK 60A890216')->count())->toBe(2);
    expect($first->progress['V_FLIS_PART.CSV']['rows'])->toBe($first->progress['V_FLIS_PART.CSV']['stored'] + 1);
});

it('binds numeric looking catalog identities as strings so MySQL can use the full index', function () {
    DB::enableQueryLog();
    PubLogFixtures::ready($this->directory.'/source', change: function ($files) {
        foreach ($files as &$contents) {
            $contents = str_replace(['"000000042"', '"24039"'], ['"123456789"', '"12345"'], $contents);
        }

        return $files;
    });
    $queries = collect(DB::getQueryLog())->filter(fn ($query) => str_starts_with($query['query'], 'select') && str_contains($query['query'], ' in ('));
    $cageBindings = $queries->filter(fn ($query) => str_contains($query['query'], 'cage_entities'))->pluck('bindings')->flatten()->all();
    $itemBindings = $queries->filter(fn ($query) => str_contains($query['query'], 'nsn_catalog_items'))->pluck('bindings')->flatten()->all();
    DB::disableQueryLog();
    expect(in_array('12345', $cageBindings, true))->toBeTrue()
        ->and(in_array(12345, $cageBindings, true))->toBeFalse()
        ->and(in_array('123456789', $itemBindings, true))->toBeTrue()
        ->and(in_array(123456789, $itemBindings, true))->toBeFalse();
});

it('scopes relationship duplicate checks by kind so MySQL can use the full identity index', function () {
    DB::enableQueryLog();
    PubLogFixtures::ready($this->directory.'/source');
    $queries = collect(DB::getQueryLog())->filter(fn ($query) => str_starts_with($query['query'], 'select')
        && str_contains($query['query'], 'nsn_catalog_relationships') && str_contains($query['query'], ' in ('));
    DB::disableQueryLog();
    expect($queries)->not->toBeEmpty();
    foreach ($queries as $query) {
        expect($query['query'])->toMatch('/["`]kind["`] = \?/')
            ->and(array_intersect(['standardization', 'cancellation'], $query['bindings']))->not->toBeEmpty();
    }
});

it('preserves nonstandard source CAGE codes with explicit validity flags and counts', function () {
    $import = PubLogFixtures::ready($this->directory.'/source', change: function ($files) {
        foreach ($files as &$contents) {
            $contents = str_replace('"24039"', '"`Q9C4"', $contents);
        }

        return $files;
    });
    $entity = CageEntity::where('cage_code', '`Q9C4')->firstOrFail();
    $reference = NsnReference::where('cage_code', '`Q9C4')->firstOrFail();
    expect($import->status)->toBe('ready')
        ->and($entity->code_valid)->toBeFalse()
        ->and($entity->summary['CAGE_CODE'])->toBe('`Q9C4')
        ->and($entity->toArray()['code_valid'])->toBeFalse()
        ->and($reference->cage_code_valid)->toBeFalse()
        ->and($reference->toArray()['cage_code_valid'])->toBeFalse()
        ->and($import->validation['invalid_cage_source_rows']['P_CAGE.CSV'])->toBe(1)
        ->and($import->validation['invalid_cage_source_rows']['V_CAGE_ADDRESS.CSV'])->toBe(1)
        ->and($import->validation['invalid_cage_source_rows']['V_FLIS_PART.CSV'])->toBeGreaterThan(0)
        ->and(CageEntity::where('cage_code', '0CEN3')->first()->code_valid)->toBeTrue();
});

it('handles quoted commas newlines and Windows-1252 definitions', function () {
    $import = PubLogFixtures::ready($this->directory.'/source', change: function ($files) {
        $files['P_FLIS_NSN.CSV'] = str_replace('"CK FILTER ASSEMBLY"', '"FILTER, ""SPECIAL""'."\n".'ASSEMBLY"', $files['P_FLIS_NSN.CSV']);
        $files['P_HELP.CSV'] .= '"TEST","LABEL","A'.chr(0x96).'B",""'."\r\n";

        return $files;
    });
    expect(NsnCatalogItem::where('niin', '000000042')->value('item_name'))->toBe("FILTER, \"SPECIAL\"\nASSEMBLY");
    expect(DB::table('pub_log_definitions')->where('import_id', $import->id)->where('code', 'TEST')->value('description'))->toBe('A–B');
});

it('round trips every reference and relationship source field through compact columns', function () {
    $import = PubLogFixtures::ready($this->directory.'/source');
    foreach (['V_FLIS_PART.CSV', 'V_FLIS_STANDARDIZATION.CSV', 'V_FLIS_CANCELLED_NIIN.CSV'] as $member) {
        $stream = fopen(base_path('tests/Fixtures/publog/'.$member), 'rb');
        $header = fgetcsv($stream, escape: '');
        $number = 0;
        while (($values = fgetcsv($stream, escape: '')) !== false) {
            $row = array_combine($header, $values);
            $number++;
            if ($member === 'V_FLIS_PART.CSV') {
                $record = NsnReference::where('import_id', $import->id)->where('source_row', $number)->firstOrFail();
                $restored = ['NIIN' => $record->niin, 'PART_NUMBER' => $record->part_number, 'CAGE_CODE' => $record->cage_code, ...$record->codes];
            } else {
                $kind = $member === 'V_FLIS_STANDARDIZATION.CSV' ? 'standardization' : 'cancellation';
                $record = NsnCatalogRelationship::where('import_id', $import->id)->where('kind', $kind)->where('source_row', $number)->firstOrFail();
                $restored = $record->data;
            }
            expect($restored)->toBe($row)
                ->and(strlen($record->getRawOriginal('row_hash')))->toBe(32);
            ksort($row);
            expect($record->row_hash)->toBe(hash('sha256', json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)))
                ->and(json_decode($record->toJson(), true, flags: JSON_THROW_ON_ERROR)['row_hash'])->toBe($record->row_hash);
        }
        fclose($stream);
    }
});

it('preserves and reports the short numeric item code observed in the official release', function () {
    $import = PubLogFixtures::ready($this->directory.'/source', change: function ($files) {
        $lines = explode("\r\n", trim($files['P_FLIS_NSN.CSV']));
        $row = str_getcsv($lines[1], escape: '');
        $row[2] = '7419';
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $row, escape: '', eol: "\r\n");
        rewind($stream);
        $lines[1] = rtrim(stream_get_contents($stream), "\r\n");
        fclose($stream);
        $files['P_FLIS_NSN.CSV'] = implode("\r\n", $lines)."\r\n";

        return $files;
    });
    expect($import->status)->toBe('ready')
        ->and(NsnCatalogItem::where('source_row', 1)->value('inc'))->toBe('7419')
        ->and($import->validation['short_item_name_codes'])->toBe(1);
});

it('resumes only committed batches after interruption without duplicating rows', function () {
    $service = app(PubLogImporter::class);
    $source = PubLogFixtures::archives($this->directory.'/source');
    $import = $service->prepare($source, '2026-10-01');
    expect(fn () => $service->run($import, report: function ($member, $progress) {
        if ($member === 'P_FLIS_NSN.CSV' && $progress['rows'] === 2) {
            throw new RuntimeException('Simulated worker interruption');
        }
    }))->toThrow(RuntimeException::class, 'Simulated');
    expect($import->fresh()->status)->toBe('failed')->and(NsnCatalogItem::count())->toBe(2);
    $result = $service->run($import);
    expect($result->status)->toBe('ready')->and(NsnCatalogItem::count())->toBe(5)
        ->and($result->progress['P_FLIS_NSN.CSV']['rows'])->toBe(5);
});

it('removes a completed spool after interruption between commit and cleanup', function () {
    $service = app(PubLogImporter::class);
    $import = $service->prepare(PubLogFixtures::archives($this->directory.'/source'), '2026-10-01');
    expect(fn () => $service->run($import, report: function ($member, $progress) {
        if ($member === 'P_FLIS_NSN.CSV' && $progress['done']) {
            throw new RuntimeException('Interrupted before cleanup');
        }
    }))->toThrow(RuntimeException::class, 'Interrupted');
    $path = $import->manifest['directory'].'/P_FLIS_NSN.CSV.spool';
    expect(is_file($path))->toBeTrue();
    expect($service->run($import)->status)->toBe('ready')->and(is_file($path))->toBeFalse();
});

it('leaves the prior snapshot readable after a malformed release', function () {
    $catalog = app(PubLogCatalog::class);
    $active = PubLogFixtures::ready($this->directory.'/good');
    $catalog->activate($active->id, null);
    $captured = $catalog->snapshot();
    $source = PubLogFixtures::archives($this->directory.'/bad', function ($files) {
        $files['V_FLIS_PART.CSV'] .= '"000000042","unterminated'."\r\n";

        return $files;
    });
    $service = app(PubLogImporter::class);
    $bad = $service->prepare($source, '2026-10-02');
    expect(fn () => $service->run($bad))->toThrow(RuntimeException::class, 'Unterminated');
    expect($bad->fresh()->status)->toBe('failed')->and($catalog->activeId())->toBe($active->id)
        ->and(NsnReference::where('import_id', $captured->id)->count())->toBeGreaterThan(0);
    expect(fn () => $catalog->activate($bad->id, $active->id))->toThrow(RuntimeException::class, 'validated');
});

it('rejects conflicting item records rather than keeping the last value', function () {
    $source = PubLogFixtures::archives($this->directory.'/source', function ($files) {
        $lines = explode("\r\n", trim($files['P_FLIS_NSN.CSV']));
        $files['P_FLIS_NSN.CSV'] .= str_replace('"UNKNOWN"', '"CONFLICT"', $lines[1])."\r\n";

        return $files;
    });
    $service = app(PubLogImporter::class);
    $import = $service->prepare($source, '2026-10-01');
    expect(fn () => $service->run($import))->toThrow(RuntimeException::class, 'Conflicting');
    expect(app(PubLogCatalog::class)->activeId())->toBeNull();
});

it('preserves references with unresolved CAGEs but rejects missing item joins', function () {
    $import = PubLogFixtures::ready($this->directory.'/unresolved', change: function ($files) {
        $files['V_FLIS_PART.CSV'] = str_replace('"24039"', '"ZZZZZ"', $files['V_FLIS_PART.CSV']);

        return $files;
    });
    expect($import->validation['unresolved_cage_references'])->toBeGreaterThan(0)
        ->and(NsnReference::where('cage_code', 'ZZZZZ')->count())->toBeGreaterThan(0);
    $service = app(PubLogImporter::class);
    $source = PubLogFixtures::archives($this->directory.'/orphan', function ($files) {
        $files['V_FLIS_PART.CSV'] = str_replace('"000000042"', '"999999999"', $files['V_FLIS_PART.CSV']);

        return $files;
    });
    $bad = $service->prepare($source, '2026-10-02');
    expect(fn () => $service->run($bad))->toThrow(RuntimeException::class, 'no matching NIIN');
});

it('rejects schema drift and changed archives on resume', function () {
    $service = app(PubLogImporter::class);
    $source = PubLogFixtures::archives($this->directory.'/schema', function ($files) {
        $files['P_CAGE.CSV'] = str_replace('"CAGE_STATUS"', '"OLD_STATUS"', $files['P_CAGE.CSV']);

        return $files;
    });
    $bad = $service->prepare($source, '2026-10-01');
    expect(fn () => $service->run($bad))->toThrow(RuntimeException::class, 'header');
    $owned = $bad->manifest['archives']['CAGE']['path'];
    file_put_contents($owned, 'corrupted');
    expect(fn () => $service->run($bad))->toThrow(RuntimeException::class, 'changed');
});

it('rejects unsafe members missing files oversized archives and incoherent releases', function () {
    $service = app(PubLogImporter::class);
    $source = PubLogFixtures::archives($this->directory.'/source');
    $zip = new ZipArchive;
    $zip->open($source.'/CAGE.zip');
    $zip->addFromString('../escape.csv', 'unsafe');
    $zip->close();
    expect(fn () => $service->prepare($source, '2026-10-01'))->toThrow(RuntimeException::class, 'Unexpected');
    PubLogFixtures::archives($source, function ($files) {
        unset($files['P_CAGE.CSV']);

        return $files;
    });
    expect(fn () => $service->prepare($source, '2026-10-01'))->toThrow(RuntimeException::class, 'Missing required');
    PubLogFixtures::archives($source);
    $zip->open($source.'/CAGE.zip');
    $zip->setMtimeName('P_CAGE.CSV', strtotime('2026-08-01'));
    $zip->close();
    expect(fn () => $service->prepare($source, '2026-10-01'))->toThrow(RuntimeException::class, 'coherent');
    config(['nsn_lookup.max_archive_bytes' => 1]);
    expect(fn () => $service->prepare($source, '2026-10-01'))->toThrow(RuntimeException::class, 'oversized');
});

it('detects CRC failures and refuses competing import workers', function () {
    $service = app(PubLogImporter::class);
    $import = $service->prepare(PubLogFixtures::archives($this->directory.'/source'), '2026-10-01');
    $lock = Cache::lock('publog:writer', 100);
    $lock->get();
    try {
        expect(fn () => $service->run($import))->toThrow(RuntimeException::class, 'Another');
    } finally {
        $lock->release();
    }
    $manifest = $import->manifest;
    $manifest['archives']['IDENTIFICATION']['members']['P_FLIS_NSN.CSV']['crc'] = '00000000';
    $import->update(['manifest' => $manifest]);
    expect(fn () => $service->run($import))->toThrow(RuntimeException::class, 'CRC');
});

it('uses compare and swap for activation and supports explicit rollback while retaining snapshots', function () {
    $catalog = app(PubLogCatalog::class);
    $one = PubLogFixtures::ready($this->directory.'/one');
    $catalog->activate($one->id, null);
    $captured = $catalog->snapshot();
    $two = PubLogFixtures::ready($this->directory.'/two', '2026-10-02');
    $three = PubLogFixtures::ready($this->directory.'/three', '2026-10-03');
    $catalog->activate($two->id, $one->id);
    expect(fn () => $catalog->activate($three->id, $one->id))->toThrow(RuntimeException::class, 'changed');
    expect($catalog->activeId())->toBe($two->id)->and(NsnCatalogItem::where('import_id', $captured->id)->count())->toBe(5);
    expect(fn () => $catalog->activate($one->id, $two->id))->toThrow(RuntimeException::class, 'rollback');
    $one->update(['pinned' => true]);
    $catalog->activate($one->id, $two->id, rollback: true);
    expect($catalog->activeId())->toBe($one->id)->and(PubLogImport::count())->toBe(3)->and($one->fresh()->pinned)->toBeTrue();
});

it('guards activation on checkpoint counts and unexpected catalog shrink', function () {
    $catalog = app(PubLogCatalog::class);
    $one = PubLogFixtures::ready($this->directory.'/one');
    $catalog->activate($one->id, null);
    $two = PubLogFixtures::ready($this->directory.'/two', '2026-10-02');
    DB::table('nsn_references')->where('import_id', $two->id)->limit(1)->delete();
    expect(fn () => $catalog->validate($two))->toThrow(RuntimeException::class, 'count mismatch');
    $source = PubLogFixtures::archives($this->directory.'/shrink', function ($files) {
        $lines = explode("\r\n", trim($files['V_FLIS_PART.CSV']));
        $files['V_FLIS_PART.CSV'] = $lines[0]."\r\n".$lines[1]."\r\n";

        return $files;
    });
    $service = app(PubLogImporter::class);
    $small = $service->prepare($source, '2026-10-03');
    expect(fn () => $service->run($small))->toThrow(RuntimeException::class, 'shrink');
    expect($catalog->activeId())->toBe($one->id);
});

it('exposes operator commands and explicit stale status', function () {
    $source = PubLogFixtures::archives($this->directory.'/source');
    $this->artisan('publog:import', ['directory' => $source, '--release' => '2026-10-01'])->assertSuccessful();
    $id = PubLogImport::first()->id;
    $this->artisan('publog:activate', ['import' => $id])->assertFailed();
    $this->artisan('publog:activate', ['import' => $id, '--expected-current' => 'none'])->assertSuccessful();
    $this->artisan('publog:status', ['--json' => true])->assertSuccessful();
    $this->travelTo(now()->setDate(2026, 12, 1));
    expect(app(PubLogCatalog::class)->status()['stale'])->toBeTrue();
});
