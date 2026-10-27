<?php

/**
 * DIBBS Phase 1 probe: honest, polite, read-only access to public DIBBS pages and files.
 *
 * - Identifies itself (no browser User-Agent spoofing).
 * - Accepts the DoD consent banner once, as a browser visitor does (user-approved 2026-10-04).
 * - Waits >= 2 s between requests.
 *
 * Usage (project root): php ai/modules/dibbs-rfq-tracking/probe.php <path-or-url> [more ...]
 * Saves each response into ai/modules/dibbs-rfq-tracking/reference/ and prints a summary.
 */

require __DIR__.'/../../../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;

$base = 'https://www.dibbs.bsm.dla.mil';
$out = __DIR__.'/reference';
@mkdir($out, 0755, true);

$jar = new FileCookieJar($out.'/.cookies.json', true);
$http = new Client([
    'base_uri' => $base,
    'cookies' => $jar,
    'allow_redirects' => ['max' => 5, 'track_redirects' => true],
    'http_errors' => false,
    'timeout' => 60,
    'headers' => ['User-Agent' => 'ColoradoSupplyRfqTracker/0.1 (DIBBS file-access research probe)'],
]);

$pause = fn () => sleep(2);

/** GET a path; if the DoD consent banner comes back, accept it once and retry. */
$get = function (string $path) use ($http, $pause, $base) {
    $res = $http->get($path);
    $body = (string) $res->getBody();

    if (str_contains($body, 'id="butAgree"')) {
        preg_match('/<form[^>]*action="([^"]+)"/', $body, $action);
        preg_match_all('/<input[^>]*type="hidden"[^>]*name="([^"]+)"[^>]*value="([^"]*)"/', $body, $hidden, PREG_SET_ORDER);
        $fields = ['butAgree' => 'OK'];
        foreach ($hidden as [, $name, $value]) {
            $fields[$name] = html_entity_decode($value);
        }
        // Resolve the form action against the URL that actually served the banner:
        // the last redirect, or the requested URL itself (dibbs2 serves it without redirecting).
        $redirects = $res->getHeader('X-Guzzle-Redirect-History');
        $bannerUrl = end($redirects) ?: (str_starts_with($path, 'http') ? $path : $base.$path);
        $postUrl = (string) GuzzleHttp\Psr7\UriResolver::resolve(new GuzzleHttp\Psr7\Uri($bannerUrl), new GuzzleHttp\Psr7\Uri(html_entity_decode($action[1] ?? '')));

        $pause();
        fwrite(STDERR, "  consent banner -> POST {$postUrl}\n");
        $http->post($postUrl, ['form_params' => $fields]);
        $pause();
        $res = $http->get($path);
        $body = (string) $res->getBody();
    }

    return [$res, $body];
};

foreach (array_slice($argv, 1) as $path) {
    [$res, $body] = $get($path);
    $type = $res->getHeaderLine('Content-Type');
    $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', trim(parse_url($path, PHP_URL_PATH) ?: $path, '/'));
    if (preg_match('/^text\/html/i', $type) && ! str_ends_with($name, '.html')) {
        $name .= '.html';
    }
    file_put_contents("{$out}/{$name}", $body);

    $isBanner = str_contains($body, 'id="butAgree"');
    printf("%-55s status=%d type=%-28s bytes=%-8d banner=%s -> reference/%s\n",
        $path, $res->getStatusCode(), substr($type, 0, 28), strlen($body), $isBanner ? 'STILL' : 'no', $name);
    $pause();
}
