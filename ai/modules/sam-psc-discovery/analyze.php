<?php

/**
 * Phase 1 analysis of probe-<label>.json rows: who posts each PSC group, notice
 * types, set-asides, response windows, and how many our NAICS list already finds.
 *
 * Usage: php ai/modules/sam-psc-discovery/analyze.php <label>
 */

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ours = array_merge(config('sam_opportunities.naics_codes.primary'), config('sam_opportunities.naics_codes.secondary'));
$data = json_decode(file_get_contents(__DIR__.'/probe-'.($argv[1] ?? 'fsgrows').'.json'), true);

$tally = function (array $rows, callable $key, int $top = 4): string {
    $counts = [];
    foreach ($rows as $r) {
        $k = $key($r) ?? 'none';
        $counts[$k] = ($counts[$k] ?? 0) + 1;
    }
    arsort($counts);

    return json_encode(array_slice($counts, 0, $top, true));
};

foreach ($data['codes'] as $code => $r) {
    $rows = $r['rows'] ?? [];
    $n = count($rows);
    $found = count(array_filter($rows, fn ($x) => in_array($x['naics'], $ours, true)));
    $windows = array_values(array_filter(array_map(
        fn ($x) => ($x['due'] && $x['posted']) ? (strtotime($x['due']) - strtotime($x['posted'])) / 86400 : null,
        $rows,
    ), fn ($v) => $v !== null));
    sort($windows);

    printf("== %s  n=%d  found by our NAICS=%d (%d%%)  median days to respond=%s\n",
        $code, $n, $found, $n ? round(100 * $found / $n) : 0, $windows ? round($windows[intdiv(count($windows), 2)]) : '?');
    echo '  agency:    '.$tally($rows, fn ($x) => implode('.', array_slice(explode('.', $x['agency'] ?? '?'), 0, 3)), 3)."\n";
    echo '  type:      '.$tally($rows, fn ($x) => $x['type'])."\n";
    echo '  set-aside: '.$tally($rows, fn ($x) => $x['set_aside'], 3)."\n";
    echo '  naics:     '.$tally($rows, fn ($x) => $x['naics'], 6)."\n";
    echo '  e.g.:      '.implode(' | ', array_map(fn ($x) => $x['title'], array_slice($rows, 0, 6)))."\n";
}
