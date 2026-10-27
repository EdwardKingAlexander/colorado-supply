<?php

namespace App\Services\Dibbs;

use Carbon\Carbon;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Polite, read-only access to DIBBS's public daily RFQ files.
 *
 * Encodes the contract verified in ai/modules/dibbs-rfq-tracking/findings.md:
 * - Honest User-Agent (config dibbs.user_agent); never a browser string.
 * - The DoD consent banner appears once per host (www and dibbs2) and is
 *   accepted the way a browser visitor accepts it.
 * - File downloads never follow redirects: a missing file is a 404, or a 302
 *   to a dead http:// address that hangs.
 * - Content is validated, because the banner itself is a 200 text/html page.
 * - dibbs.request_interval_seconds between requests.
 *
 * It never logs in, stores credentials, or submits anything but the banner.
 */
class DibbsClient
{
    protected CookieJar $cookies;

    protected bool $requestedBefore = false;

    public function __construct()
    {
        $this->cookies = new CookieJar;
    }

    /**
     * Posting days on the "Recent RFQs" page, newest first.
     *
     * @return array<int, array{posting_date: Carbon, in: ?string, bq: ?string}>
     *
     * @throws DibbsAccessException
     */
    public function listRecentFiles(): array
    {
        $response = $this->getPassingConsent(config('dibbs.listing_url'), followRedirects: true);

        if (! $response->successful()) {
            throw new DibbsAccessException("DIBBS RFQ listing returned HTTP {$response->status()}.");
        }

        $days = [];

        foreach ($this->rows($response->body()) as $row) {
            if (! preg_match('/(\d{2})-(\d{2})-(\d{4})/', $row, $d)) {
                continue;
            }

            $in = $this->link($row, '/in\d{6}\.txt/i');
            $bq = $this->link($row, '/bq\d{6}\.zip/i');

            if ($in === null && $bq === null) {
                continue;
            }

            $days[] = [
                'posting_date' => Carbon::create((int) $d[3], (int) $d[1], (int) $d[2])->startOfDay(),
                'in' => $in,
                'bq' => $bq,
            ];
        }

        if ($days === []) {
            throw new DibbsAccessException('DIBBS RFQ listing had no recognizable file rows; the page layout may have changed.');
        }

        usort($days, fn ($a, $b) => $b['posting_date'] <=> $a['posting_date']);

        return $days;
    }

    /**
     * Download a daily file. Returns null when DIBBS reports it missing.
     *
     * @throws DibbsAccessException on any other unexpected response
     */
    public function download(string $url): ?string
    {
        $this->assertDibbsHost($url);

        $response = $this->getPassingConsent($url, followRedirects: false);

        if ($response->status() === 404 || $response->redirect()) {
            return null;
        }

        if (! $response->successful()) {
            throw new DibbsAccessException("DIBBS returned HTTP {$response->status()} for ".basename($url).'.');
        }

        $body = $response->body();
        $isZip = str_ends_with(strtolower($url), '.zip');

        if ($isZip ? ! str_starts_with($body, "PK\x03\x04") : $this->looksLikeHtml($body)) {
            throw new DibbsAccessException('DIBBS returned a web page instead of '.basename($url).'; the consent flow or file location may have changed.');
        }

        return $body;
    }

    /**
     * GET a URL; if the DoD consent banner comes back, accept it once and retry.
     */
    protected function getPassingConsent(string $url, bool $followRedirects): Response
    {
        $response = $this->request($followRedirects)->get($url);

        if (! $this->isConsentBanner($response->body())) {
            return $response;
        }

        $this->acceptConsent($response, $url);

        $response = $this->request($followRedirects)->get($url);

        if ($this->isConsentBanner($response->body())) {
            throw new DibbsAccessException('DIBBS kept showing the DoD consent banner after it was accepted; cookies or the banner form may have changed.');
        }

        return $response;
    }

    protected function acceptConsent(Response $banner, string $requestedUrl): void
    {
        $html = $banner->body();

        if (! preg_match('/<form[^>]*action="([^"]+)"/i', $html, $action)) {
            throw new DibbsAccessException('DIBBS consent banner has no form; its layout may have changed.');
        }

        $fields = ['butAgree' => 'OK'];

        if (preg_match_all('/<input[^>]*type="hidden"[^>]*>/i', $html, $inputs)) {
            foreach ($inputs[0] as $input) {
                if (preg_match('/name="([^"]+)"/i', $input, $n)) {
                    preg_match('/value="([^"]*)"/i', $input, $v);
                    $fields[html_entity_decode($n[1])] = html_entity_decode($v[1] ?? '');
                }
            }
        }

        // Resolve against the page that served the banner: www redirects to it,
        // dibbs2 serves it in place of the requested file.
        $base = (string) ($banner->effectiveUri() ?? $requestedUrl);
        $target = (string) UriResolver::resolve(new Uri($base), new Uri(html_entity_decode($action[1])));
        $this->assertDibbsHost($target);

        $this->request(true)->asForm()->post($target, $fields);
    }

    protected function request(bool $followRedirects): PendingRequest
    {
        $this->pause();

        return Http::withUserAgent((string) config('dibbs.user_agent'))
            ->timeout((int) config('dibbs.timeout_seconds', 60))
            ->withOptions([
                'cookies' => $this->cookies,
                'allow_redirects' => $followRedirects ? ['max' => 5, 'track_redirects' => true] : false,
            ]);
    }

    protected function pause(): void
    {
        $seconds = (int) config('dibbs.request_interval_seconds', 2);

        if ($this->requestedBefore && $seconds > 0) {
            sleep($seconds);
        }

        $this->requestedBefore = true;
    }

    protected function isConsentBanner(string $body): bool
    {
        return str_contains($body, 'id="butAgree"');
    }

    protected function looksLikeHtml(string $body): bool
    {
        return preg_match('/^\s*(<!doctype|<html|<head|<body)/i', substr($body, 0, 512)) === 1;
    }

    /**
     * @return array<int, string> raw <tr> contents
     */
    protected function rows(string $html): array
    {
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $html, $m);

        return $m[1];
    }

    protected function link(string $rowHtml, string $namePattern): ?string
    {
        if (! preg_match_all('/href=[\'"]([^\'"]+)[\'"]/i', $rowHtml, $hrefs)) {
            return null;
        }

        foreach ($hrefs[1] as $href) {
            $href = html_entity_decode($href);

            if (preg_match($namePattern, basename(parse_url($href, PHP_URL_PATH) ?? ''))) {
                $url = str_starts_with($href, 'http') ? $href : rtrim(config('dibbs.files_base_url'), '/').'/'.basename($href);

                return $this->isDibbsHost($url) ? $url : null;
            }
        }

        return null;
    }

    protected function isDibbsHost(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return parse_url($url, PHP_URL_SCHEME) === 'https' && str_ends_with($host, '.bsm.dla.mil');
    }

    protected function assertDibbsHost(string $url): void
    {
        if (! $this->isDibbsHost($url)) {
            throw new DibbsAccessException("Refusing to contact a non-DIBBS address: {$url}");
        }
    }
}
