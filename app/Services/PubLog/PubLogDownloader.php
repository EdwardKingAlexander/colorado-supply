<?php

namespace App\Services\PubLog;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PubLogDownloader
{
    private function url(string $name): string
    {
        $base = config('nsn_lookup.source_base_url');
        if (parse_url($base, PHP_URL_SCHEME) !== 'https' || parse_url($base, PHP_URL_HOST) !== 'www.dla.mil') {
            throw new RuntimeException('PUB LOG downloads must use the official HTTPS DLA host.');
        }

        return rtrim($base, '/').'/'.$name.'.zip';
    }

    /** Small metadata requests only: no automatic download, import, or activation. */
    public function check(): array
    {
        $lock = Cache::lock('publog:check', 120);
        if (! $lock->get()) {
            throw new RuntimeException('A PUB LOG metadata check is already running.');
        }
        try {
            $metadata = [];
            foreach (['IDENTIFICATION', 'CAGE', 'REFERENCE'] as $name) {
                $response = Http::withUserAgent(config('nsn_lookup.user_agent'))
                    ->connectTimeout(5)->timeout(config('nsn_lookup.request_timeout'))
                    ->withoutRedirecting()->head($this->url($name));
                if (! $response->successful()) {
                    throw new RuntimeException("DLA {$name} metadata returned HTTP {$response->status()}; use official browser downloads and local import if access is denied.");
                }
                $metadata[$name] = ['etag' => $response->header('ETag'), 'last_modified' => $response->header('Last-Modified'), 'bytes' => $response->header('Content-Length')];
                if (! $metadata[$name]['etag'] && ! $metadata[$name]['last_modified']) {
                    throw new RuntimeException('DLA did not return usable release metadata.');
                }
            }
            $previous = PubLogDb::connection()->table('pub_log_catalog_state')->where('id', 1)->value('remote_manifest');
            $status = $previous === null ? 'available' : (json_decode($previous, true) === $metadata ? 'unchanged' : 'changed');
            PubLogDb::connection()->table('pub_log_catalog_state')->where('id', 1)->update([
                'last_checked_at' => now(), 'check_status' => $status, 'remote_manifest' => json_encode($metadata, JSON_THROW_ON_ERROR), 'check_error' => null, 'updated_at' => now(),
            ]);

            return ['status' => $status, 'archives' => $metadata];
        } catch (Throwable $exception) {
            PubLogDb::connection()->table('pub_log_catalog_state')->where('id', 1)->update([
                'last_checked_at' => now(), 'check_status' => 'failed', 'check_error' => mb_substr($exception->getMessage(), 0, 4000), 'updated_at' => now(),
            ]);
            throw $exception;
        } finally {
            $lock->release();
        }
    }

    /** Explicit operator action; streams to private disk and never buffers a ZIP in memory. */
    public function download(): string
    {
        $root = config('nsn_lookup.directory');
        File::ensureDirectoryExists($root);
        app(PubLogArchives::class)->assertSpace($root, 3 * config('nsn_lookup.max_archive_bytes'));
        $directory = $root.DIRECTORY_SEPARATOR.'download-'.Str::uuid();
        File::ensureDirectoryExists($directory);
        $manifest = [];
        foreach (['IDENTIFICATION', 'CAGE', 'REFERENCE'] as $name) {
            $path = $directory.DIRECTORY_SEPARATOR.$name.'.zip';
            $url = $this->url($name);
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                try {
                    $response = Http::withUserAgent(config('nsn_lookup.user_agent'))
                        ->connectTimeout(5)->timeout(300)->withoutRedirecting()
                        ->withOptions([
                            'sink' => $path,
                            'progress' => static function ($total, $downloaded) {
                                if ($total > config('nsn_lookup.max_archive_bytes') || $downloaded > config('nsn_lookup.max_archive_bytes')) {
                                    throw new RuntimeException('Download exceeded the archive size limit.');
                                }
                            },
                        ])->get($url);
                    if ($response->successful()) {
                        clearstatcache(true, $path);
                        if (! is_file($path) || filesize($path) > config('nsn_lookup.max_archive_bytes')
                            || ($response->header('Content-Length') !== '' && (int) $response->header('Content-Length') !== filesize($path))) {
                            throw new RuntimeException('Downloaded archive has an invalid length.');
                        }
                        $manifest[$name] = ['url' => $url, 'etag' => $response->header('ETag'), 'last_modified' => $response->header('Last-Modified'), 'sha256' => hash_file('sha256', $path), 'bytes' => filesize($path)];
                        break;
                    }
                    if ($attempt === 3 || (! $response->serverError() && $response->status() !== 429)) {
                        throw new RuntimeException("DLA {$name} download returned HTTP {$response->status()}; download from the official reading room and use publog:import instead.");
                    }
                } catch (ConnectionException $exception) {
                    if ($attempt === 3) {
                        throw $exception;
                    }
                }
                usleep(250000 * $attempt);
            }
        }
        file_put_contents($directory.DIRECTORY_SEPARATOR.'download-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return $directory;
    }
}
