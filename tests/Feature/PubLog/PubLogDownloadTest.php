<?php

use App\Services\PubLog\PubLogCatalog;
use App\Services\PubLog\PubLogDownloader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;
use Tests\Support\PubLogFixtures;

beforeEach(function () {
    $this->directory = sys_get_temp_dir().'/publog-download-'.bin2hex(random_bytes(8));
    config(['nsn_lookup.directory' => $this->directory.'/owned', 'nsn_lookup.minimum_free_bytes' => 0, 'nsn_lookup.batch_size' => 500]);
    Http::preventStrayRequests();
    $this->travelTo(now()->setDate(2026, 10, 4));
});
afterEach(function () {
    File::deleteDirectory($this->directory);
});

it('reports blocked metadata truthfully and preserves the active catalog', function () {
    $import = PubLogFixtures::ready($this->directory.'/source');
    $catalog = app(PubLogCatalog::class);
    $catalog->activate($import->id, null);
    Http::fake(['*' => Http::response('Access denied', 403)]);
    $this->artisan('publog:check')->assertFailed();
    expect($catalog->activeId())->toBe($import->id)
        ->and($catalog->status()['check_status'])->toBe('failed')
        ->and($catalog->status()['check_error'])->toContain('403');
    Http::assertSentCount(1);
});

it('tracks changed and unchanged metadata without downloads or activation', function () {
    $version = 'one';
    Http::fake(function () use (&$version) {
        return Http::response('', 200, ['ETag' => $version, 'Last-Modified' => 'Thu, 01 Oct 2026 00:00:00 GMT', 'Content-Length' => '100']);
    });
    $downloader = app(PubLogDownloader::class);
    expect($downloader->check()['status'])->toBe('available');
    expect($downloader->check()['status'])->toBe('unchanged');
    $version = 'two';
    expect($downloader->check()['status'])->toBe('changed')
        ->and(app(PubLogCatalog::class)->activeId())->toBeNull();
    Http::assertSent(fn ($request) => $request->method() === 'HEAD');
});

it('streams the three downloads and records their hashes', function () {
    $source = PubLogFixtures::archives($this->directory.'/source');
    foreach (['IDENTIFICATION', 'CAGE', 'REFERENCE'] as $name) {
        $data = file_get_contents($source.'/'.$name.'.zip');
        Http::fake(['*/'.$name.'.zip' => Http::response($data, 200, ['Content-Length' => (string) strlen($data), 'ETag' => $name])]);
    }
    $downloaded = app(PubLogDownloader::class)->download();
    $manifest = json_decode(file_get_contents($downloaded.'/download-manifest.json'), true);
    foreach (['IDENTIFICATION', 'CAGE', 'REFERENCE'] as $name) {
        expect(hash_file('sha256', $downloaded.'/'.$name.'.zip'))->toBe(hash_file('sha256', $source.'/'.$name.'.zip'))
            ->and($manifest[$name]['sha256'])->toBe(hash_file('sha256', $source.'/'.$name.'.zip'));
    }
    Http::assertSentCount(3);
    expect(DB::table('pub_log_imports')->count())->toBe(0);
});

it('does not retry access denial or follow redirects and bounds transient retries', function () {
    Http::fake(['*' => Http::sequence()->push('denied', 403)->push('retry', 503)->push('retry', 503)->push('retry', 503)->push('', 302, ['Location' => 'https://example.com/untrusted.zip'])]);
    expect(fn () => app(PubLogDownloader::class)->download())->toThrow(RuntimeException::class, '403');
    Http::assertSentCount(1);
    expect(fn () => app(PubLogDownloader::class)->download())->toThrow(RuntimeException::class, '503');
    expect(count(Http::recorded()))->toBe(4);
    expect(fn () => app(PubLogDownloader::class)->download())->toThrow(RuntimeException::class, '302');
    Http::assertSentCount(5);
});

it('rejects incomplete or oversized downloads', function () {
    Http::fake(['*' => Http::sequence()->push('short', 200, ['Content-Length' => '100'])->push('large', 200)]);
    expect(fn () => app(PubLogDownloader::class)->download())->toThrow(RuntimeException::class, 'length');
    config(['nsn_lookup.max_archive_bytes' => 2]);
    expect(fn () => app(PubLogDownloader::class)->download())->toThrow(RuntimeException::class, 'length');
});

it('keeps scheduled checks opt in and registers a non-overlapping metadata task', function () {
    config(['nsn_lookup.check_enabled' => false]);
    $this->artisan('publog:check', ['--scheduled' => true])->assertSuccessful();
    Http::assertNothingSent();
    $event = collect(Schedule::events())->first(fn ($event) => str_contains($event->command ?? '', 'publog:check'));
    expect($event)->not->toBeNull()->and($event->withoutOverlapping)->toBeTrue();
});
