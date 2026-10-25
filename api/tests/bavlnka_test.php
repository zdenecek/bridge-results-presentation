<?php

// php api/tests/bavlnka_test.php

require __DIR__ . '/../bavlnka.php';

$failures = 0;
function check(string $label, $actual, $expected): void
{
    global $failures;
    if ($actual === $expected) {
        echo "ok   $label\n";
        return;
    }
    $failures++;
    echo "FAIL $label\n  expected: " . json_encode($expected, JSON_UNESCAPED_UNICODE)
        . "\n  actual:   " . json_encode($actual, JSON_UNESCAPED_UNICODE) . "\n";
}

function fixture(string $name): string
{
    return file_get_contents(__DIR__ . '/fixtures/bavlnka/' . $name);
}

function memoryDb(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    bavlnkaEnsureSchema($pdo);
    return $pdo;
}

// Listing on bkpraha.cz
$entries = bavlnkaParseListing(fixture('listing.html'), 'https://www.bkpraha.cz/vysledky/');
check('listing finds only Bavlnka links', array_column($entries, 'url'), [
    'https://bridge.zdenektomis.eu/vysledky/2026/bkp-bavlnka/0915bavlnka/',
    'https://1url.cz/teX4p',
    'https://bridge.zdenektomis.eu/vysledky/2026/bkp-bavlnka/1013bavlnka/#000000RR0000',
]);
check('listing dates', array_column($entries, 'date'), ['2026-09-15', '2026-09-22', '2026-10-13']);

// Giborg / GoodLuck
$giborg = bavlnkaParseGiborg(fixture('giborg.html'));
check('giborg date', $giborg['date'], '2026-09-22');
check('giborg pairs', count($giborg['pairs']), 4);
check('giborg tie', [$giborg['pairs'][3]['rankFrom'], $giborg['pairs'][3]['rankTo']], [3, 4]);
check('giborg names', array_column($giborg['pairs'][3]['players'], 'name'), ['Čabrilová', 'Malý Radek']);
check('giborg pair number and score', [$giborg['pairs'][0]['pair'], $giborg['pairs'][0]['score']], [4, 69.05]);

// Tournament Calculator
$tc = bavlnkaParseTournamentCalculator(json_decode(fixture('tc-results.json'), true));
check('tc tie', array_map(fn($p) => [$p['rankFrom'], $p['rankTo']], $tc), [[1, 1], [2, 2], [3, 4], [3, 4], [5, 5]]);
check('tc players', $tc[0]['players'], [['name' => 'Olmer Adam', 'cbsId' => 3102], ['name' => 'Lemmon Bela', 'cbsId' => null]]);
check('tc score', $tc[0]['score'], 67.59);

check('placeholder first name is dropped', bavlnkaPlayerName('Hawley .'), 'Hawley');

// Real pages
$live = bavlnkaParseListing(fixture('live-listing.html'), 'https://www.bkpraha.cz/vysledky/');
check('live listing dates', array_column($live, 'date'), ['2026-09-15', '2026-09-22', '2026-09-29', '2026-10-06', '2026-10-13', '2026-10-20']);
check('live listing urls', array_slice(array_column($live, 'url'), 0, 2), [
    'https://bridge.zdenektomis.eu/vysledky/2026/bkp-bavlnka/0915bavlnka/',
    'https://1url.cz/teX4p',
]);

$liveGiborg = bavlnkaParseGiborg(fixture('live-giborg-240910.html'));
check('live giborg date', $liveGiborg['date'], '2024-09-10');
check('live giborg pairs', count($liveGiborg['pairs']), 7);
check('live giborg decodes windows-1250', array_column($liveGiborg['pairs'][0]['players'], 'name'), ['Gregor Štěpán', 'Havelková Eva']);
check('live giborg guest without first name', array_column($liveGiborg['pairs'][1]['players'], 'name'), ['Hytka Jakub', 'Hawley']);

$liveTc = bavlnkaParseTournamentCalculator(json_decode(gzdecode(fixture('live-tc-1013.json.gz')), true));
check('live tc pairs', count($liveTc), 9);
check('live tc winner', [$liveTc[0]['rankFrom'], $liveTc[0]['score'], $liveTc[0]['players']], [1, 67.59, [['name' => 'Olmer Adam', 'cbsId' => 3102], ['name' => 'Lemmon Bela', 'cbsId' => null]]]);

// Whole update with a fake web
$web = [
    'https://www.bkpraha.cz/vysledky/' => fixture('listing.html'),
    'https://bridge.zdenektomis.eu/vysledky/2026/bkp-bavlnka/1013bavlnka/' => fixture('tc-page.html'),
    'https://bridge.zdenektomis.eu/vysledky/2026/bkp-bavlnka/1013bavlnka/results.json.gz' => gzencode(fixture('tc-results.json')),
    'https://1url.cz/teX4p' => ['http://www.bridgebase.6f.sk/results/ZF/2026/260922/result.php?round_number=1', fixture('giborg.html')],
];
$requested = [];
$http = function (string $url) use ($web, &$requested): array {
    $requested[] = $url;
    $page = $web[$url] ?? null;
    if ($page === null) return ['Not found', $url, 404];
    return is_array($page) ? [$page[1], $page[0], 200] : [$page, $url, 200];
};
$pdo = memoryDb();
$report = bavlnkaUpdate($pdo, ['bavlnka' => ['sources' => ['https://www.bkpraha.cz/vysledky/']]], $http);
check('update discovers three tournaments', count($report['discovered']), 3);
check('update processes newest first', array_column($report['processed'], 'date'), ['2026-10-13', '2026-09-22']);
check('update reports the missing one', count($report['errors']), 1);
check('fragment is not requested', in_array('https://bridge.zdenektomis.eu/vysledky/2026/bkp-bavlnka/1013bavlnka/#000000RR0000', $requested, true), false);

$again = bavlnkaUpdate($pdo, ['bavlnka' => ['sources' => ['https://www.bkpraha.cz/vysledky/']]], $http);
check('second run discovers nothing new', count($again['discovered']), 0);
check('second run retries only the failed one', $again['processed'], []);

$ranking = bavlnkaRanking($pdo, []);
check('ranking updated', $ranking['updated'], '2026-10-13');
$top = $ranking['ranking'][0];
check('points add up across tournaments', [$top['name'], $top['points'], count($top['tournaments'])], ['Olmer Adam', 110, 2]);
check('csv first rows', array_slice(explode("\r\n", bavlnkaRankingCsv($ranking)), 0, 2), ['Pořadí,Jméno,Body,,Aktualizace', '1,Olmer Adam,110,,26-10-13']);

// Golden ranking: the Google Sheet ranking after the 13. 10. 2026 tournament
$tournaments = [];
$rows = [];
$current = null;
foreach (file(__DIR__ . '/fixtures/bavlnka/results-2026-10-13.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (preg_match('/^([TG]) (\d{4}-\d{2}-\d{2})$/', $line, $m)) {
        $current = count($tournaments) + 1;
        $tournaments[$current] = ['id' => $current, 'date' => $m[2], 'results_url' => null, 'source_url' => '', 'pairs' => 0, 'format' => $m[1]];
        continue;
    }
    [$rank, , $names] = explode(';', $line, 3);
    [$from, $to] = array_map('intval', explode('-', $rank) + [1 => $rank]);
    $tournaments[$current]['pairs']++;
    $separator = $tournaments[$current]['format'] === 'T' ? '/\s+\/\s+/u' : '/\s+-\s+/u';
    foreach (preg_split($separator, $names) as $name) {
        $name = trim(preg_replace('/#.*$/', '', $name));
        $rows[] = ['tournament_id' => $current, 'rank_from' => $from, 'rank_to' => $to, 'player' => $name, 'date' => $tournaments[$current]['date']];
    }
}
// TC gives ties as the same place for all tied pairs
$ties = [];
foreach ($rows as $r) $ties[$r['tournament_id']][$r['rank_from']][$r['player']] = true;
foreach ($rows as &$r) {
    if ($tournaments[$r['tournament_id']]['format'] === 'T') {
        $r['rank_to'] = $r['rank_from'] + intdiv(count($ties[$r['tournament_id']][$r['rank_from']]), 2) - 1;
    }
}
unset($r);
usort($tournaments, fn($a, $b) => strcmp($b['date'], $a['date']));

$golden = bavlnkaComputeRanking($tournaments, $rows, BAVLNKA_SEED_ALIASES, BAVLNKA_DEFAULTS);
$mine = array_column($golden['ranking'], 'points', 'name');
$expected = [];
foreach (file(__DIR__ . '/fixtures/bavlnka/ranking-2026-10-13.csv', FILE_IGNORE_NEW_LINES) as $line) {
    [, $name, $points] = explode(',', $line);
    $expected[$name] = (int) $points;
}
$differences = [];
foreach ($expected as $name => $points) {
    if (($mine[$name] ?? null) !== $points) $differences[$name] = [$points, $mine[$name] ?? null];
}
check('golden ranking matches the Google Sheet', $differences, []);
check('golden ranking has no unexpected players', array_keys(array_diff_key($mine, $expected)), []);
check('csv keeps the sheet\'s number format', explode("\r\n", bavlnkaRankingCsv($golden))[1], '1,Růžičková Blanka,1 730,,26-10-13');
check('golden ranking order', array_slice(array_column($golden['ranking'], 'rank'), 0, 8), [1, 2, 3, 4, 5, 6, 6, 8]);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
