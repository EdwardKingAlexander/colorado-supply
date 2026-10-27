<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
| DIBBS fixtures: real DLA files from 2026-10-02, trimmed to 50 RFQ lines
| (ai/modules/dibbs-rfq-tracking/make_fixtures.py).
*/

function dibbsFixture(string $name): string
{
    return file_get_contents(base_path('tests/Fixtures/dibbs/'.$name));
}

/** Build a bqYYMMDD.zip the way DLA ships it (bq + as text files). */
function dibbsBqZip(?string $bq = null, ?string $as = null): string
{
    $path = tempnam(sys_get_temp_dir(), 'bqzip');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('bq261002.txt', $bq ?? dibbsFixture('bq261002.txt'));
    $zip->addFromString('as261002.txt', $as ?? dibbsFixture('as261002.txt'));
    $zip->close();
    $contents = file_get_contents($path);
    unlink($path);

    return $contents;
}

/**
 * A fake DIBBS shaped like the real one (ai/modules/dibbs-rfq-tracking/findings.md):
 * a DoD consent banner per host until accepted, the "Recent RFQs" listing on www,
 * files on dibbs2, and missing files answered with a 404.
 */
function fakeDibbs(array $files = [], array $listedDays = ['261002', '261001'], bool $bannerSticks = false): object
{
    $state = (object) ['accepted' => [], 'userAgents' => [], 'requests' => []];

    $banner = fn () => Http::response('<html><form method="post" action="./dodwarning.aspx?goto=%2fx" id="FrmBody">'
        .'<input type="hidden" name="__VIEWSTATE" id="__VIEWSTATE" value="abc" />'
        .'<input type="submit" name="butAgree" value="OK" id="butAgree" /></form></html>', 200, ['Content-Type' => 'text/html']);

    $rows = implode('', array_map(fn ($d) => '<tr><td>'.substr($d, 2, 2).'-'.substr($d, 4, 2).'-20'.substr($d, 0, 2).'</td>'
        ."<td><a href='https://dibbs2.bsm.dla.mil/Downloads/RFQ/Archive/ca{$d}.zip'>ca{$d}.zip</a></td>"
        ."<td><a href='https://dibbs2.bsm.dla.mil/Downloads/RFQ/Archive/in{$d}.txt'>in{$d}.txt</a></td>"
        ."<td><a href='https://dibbs2.bsm.dla.mil/Downloads/RFQ/Archive/bq{$d}.zip'>bq{$d}.zip</a></td></tr>", $listedDays));

    Http::fake(function (Request $request) use ($state, $banner, $rows, $files, $bannerSticks) {
        $host = parse_url($request->url(), PHP_URL_HOST);
        $state->userAgents[] = $request->header('User-Agent')[0] ?? '';
        $state->requests[] = $request->method().' '.$request->url();

        if ($request->method() === 'POST' && str_contains($request->url(), 'dodwarning.aspx')) {
            if (($request->data()['butAgree'] ?? null) === 'OK' && ! $bannerSticks) {
                $state->accepted[$host] = true;
            }

            return Http::response('ok');
        }

        if (! ($state->accepted[$host] ?? false)) {
            return $banner();
        }

        if (str_contains($request->url(), 'RFQDates.aspx')) {
            return Http::response("<table><tr><th>Post Date</th></tr>{$rows}</table>", 200, ['Content-Type' => 'text/html']);
        }

        $name = basename(parse_url($request->url(), PHP_URL_PATH));

        return array_key_exists($name, $files)
            ? Http::response($files[$name], 200)
            : Http::response('', 404);
    });

    return $state;
}
