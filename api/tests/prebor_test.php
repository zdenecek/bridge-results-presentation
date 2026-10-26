<?php

// php api/tests/prebor_test.php

require __DIR__ . '/../prebor.php';

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
    return file_get_contents(__DIR__ . '/fixtures/prebor/' . $name);
}

function memoryDb(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    preborEnsureSchema($pdo);
    return $pdo;
}

/** Serves the saved pages instead of the network. */
function fakeHttp(array $pages): callable
{
    return function (string $url) use ($pages): array {
        foreach ($pages as $prefix => $file) {
            if ($url === $prefix) return [fixture($file), $url, 200];
        }
        return ['', $url, 404];
    };
}

/** Rank, the set of player keys and the score of each widget row, to compare regardless of name order. */
function widgetRows(string $csv): array
{
    $rows = [];
    foreach (array_slice(array_map(fn($l) => str_getcsv($l, ',', '"', ''), preg_split('/\R/', trim($csv))), 1) as $row) {
        if (($row[0] ?? '') === '') continue;
        $rows[] = [(int) $row[0], preborPlayersKey(preg_split('/\s+-\s+/u', $row[1])), $row[2]];
    }
    return $rows;
}

check('person key ignores order, diacritics and suffix', preborPersonKey('Hanák Vladimír ml.'), preborPersonKey('Vladimír HANAK'));

// Registration sheet
$roster = preborParseRoster(fixture('registration-top.csv'));
check('roster numbers', array_slice(array_column($roster, 'number'), 0, 3), [1, 2, 3]);
check('roster suffix', $roster[2]['players'], ['Vladimír Hanák ml.', 'Petr Hubený']);
check('roster third player', $roster[7]['title'], 'Jiřina Böhmová - Jan Diamant - Jiří Emmer');
check('roster skips empty numbered rows', count(array_filter($roster, fn($e) => $e['number'] > 40)), 0);

// Pasted results: Ondra's VP recalculation of IMP round 1, as CSV and as tab-separated rows
$manual = preborParseManual(fixture('imp-0930-vp.csv'));
check('manual pairs', count($manual), 17);
check('manual first', [$manual[0]['pair'], $manual[0]['title'], $manual[0]['score']], [4, 'Otakar Svoboda - Pavla Svobodová', 78.66]);
$tabbed = preborParseManual("1\t4\tOtakar Svoboda - Pavla Svobodová\t1,720\t91,41\t78,66\n2\tA B - C D\t78,66\nPoř.\tDvojice\tVP");
check('manual tabs and tie', array_map(fn($p) => [$p['rankFrom'], $p['rankTo'], $p['score']], $tabbed), [[1, 2, 78.66], [1, 2, 78.66]]);

// TOP round 1 from Tournament Calculator, ranked against the current widget
$http = fakeHttp([
    PREBOR_SEED_COMPETITIONS[0]['registration_csv'] => 'registration-top.csv',
    PREBOR_SEED_COMPETITIONS[1]['registration_csv'] => 'registration-imp.csv',
    'https://vysledky.bkpraha.cz/prezentace/2026/top-prebor/' => 'top-0916-page.html',
    'https://vysledky.bkpraha.cz/prezentace/2026/top-prebor/results.json.gz' => 'top-0916-results.json.gz',
]);
$pdo = memoryDb();
check('roster import', preborImportRoster($pdo, 'top-2026', $http), 40);
$added = preborAddRound($pdo, 'top-2026', ['url' => 'https://vysledky.bkpraha.cz/prezentace/2026/top-prebor/', 'date' => '2026-09-16'], $http);
check('round added and fetched', $added['pairs'], 15);

$top = preborRanking($pdo, 'top-2026');
$ours = array_map(fn($s) => [$s['rank'], preborPlayersKey($s['players']), number_format($s['total'], 2, ',', '')], $top['ranking']);
check('TOP after round 1 matches the widget', $ours, widgetRows(fixture('widget-top-0916.csv')));
check('TOP roster title', $top['ranking'][13]['name'], 'Jiřina Böhmová - Jan Diamant - Jiří Emmer');
check('TOP unregistered pair keeps its TC name', $top['ranking'][1]['name'], 'Milan Macura - Man Him Chu');
check('TOP csv header', explode("\r\n", preborRankingCsv($top))[1], '1,Vojtěch Franz - Mikuláš Krása,"62,82",1,26-09-16');

// IMP round 1 from the pasted VP
preborImportRoster($pdo, 'imp-2026', $http);
preborAddRound($pdo, 'imp-2026', ['date' => '2026-09-30', 'results' => fixture('imp-0930-vp.csv')], $http);
$imp = preborRanking($pdo, 'imp-2026');
// The roster has Ondřej Krása as the third player of Galbavá - Galbavý, the widget lists only the two.
$ours = array_map(fn($s) => [$s['rank'], preborPlayersKey($s['players']), number_format($s['total'], 2, ',', '')], $imp['ranking']);
$ours[3][1] = str_replace('|krasa ondrej', '', $ours[3][1]);
check('IMP after round 1 matches the widget', $ours, widgetRows(fixture('widget-imp-0930.csv')));
check('IMP Hanák ml. is the registered one', $imp['ranking'][6]['name'], 'Vladimír Hanák ml. - Petr Hubený');

// Ten synthetic rounds: best 8, substitutes, own pairs and a decision in the admin
$cfg = ['slug' => 'test', 'season' => '2026/27', 'title' => 'Test', 'unit' => '%', 'best' => 8, 'rounds' => 10];
$entries = [
    1 => ['title' => 'Anna A - Bára B - Cyril C', 'players' => ['Anna A', 'Bára B', 'Cyril C']],
    2 => ['title' => 'Dan D - Eva E', 'players' => ['Dan D', 'Eva E']],
];
$rounds = [];
$results = [];
for ($i = 1; $i <= 10; $i++) {
    $rounds[] = ['id' => $i, 'date' => sprintf('2026-10-%02d', $i), 'url' => null, 'pairs' => 3];
    $results[] = ['round_id' => $i, 'rank_from' => 1, 'rank_to' => 1, 'score' => 50 + $i, 'title' => null,
        'players' => $i === 3 ? ['C Cyril', 'Bára B'] : ($i === 4 ? ['Anna A', 'Zoe Z'] : ['A Anna', 'B Bára'])];
}
$results[] = ['round_id' => 1, 'rank_from' => 2, 'rank_to' => 2, 'score' => 40, 'title' => 'Xaver X - Yvona Y', 'players' => ['X Xaver', 'Y Yvona']];
$results[] = ['round_id' => 2, 'rank_from' => 2, 'rank_to' => 2, 'score' => 45, 'title' => null, 'players' => ['Xaver X', 'Yvona Y']];
$results[] = ['round_id' => 5, 'rank_from' => 2, 'rank_to' => 2, 'score' => 44, 'title' => null, 'players' => ['Anna A', 'Dan D']];
$results[] = ['round_id' => 6, 'rank_from' => 2, 'rank_to' => 2, 'score' => 30, 'title' => null, 'players' => ['Dan D', 'Eva E']];

$ranking = preborComputeRanking($cfg, $rounds, $results, $entries, []);
$first = $ranking['ranking'][0];
check('best 8 of 10', $first['total'], (float) array_sum(range(53, 60)));
check('the two worst rounds do not count', array_column(array_filter($first['results'], fn($r) => !$r['counted']), 'number'), [1, 2]);
check('substitute counts for the dvojice', array_column(array_filter($first['results'], fn($r) => $r['number'] === 4), 'kind'), ['substitute']);
check('own pair merges across rounds', [$ranking['ranking'][1]['name'], $ranking['ranking'][1]['total']], ['Xaver X - Yvona Y', 85.0]);
$conflict = array_values(array_filter($ranking['ranking'], fn($s) => $s['results'][0]['kind'] === 'conflict'));
check('players from two dvojice are a conflict', array_column($conflict, 'total'), [44.0]);

$decided = preborComputeRanking($cfg, $rounds, $results, $entries, [5 => [preborPlayersKey(['Anna A', 'Dan D']) => 2]]);
$dan = array_values(array_filter($decided['ranking'], fn($s) => $s['number'] === 2))[0];
check('admin decision moves the result', [$dan['total'], array_column($dan['results'], 'kind')], [74.0, ['assigned', 'registered']]);

// Competitions
check('both seeded', array_column(preborCompetitions($pdo), 'slug'), ['imp-2026', 'top-2026']);
$next = preborSaveCompetition($pdo, ['slug' => 'top-2027', 'season' => '2027/28', 'title' => 'Topový přebor BKP 2027/28',
    'unit' => '%', 'best' => 8, 'rounds' => 10]);
check('new season has its own empty ranking', preborRanking($pdo, 'top-2027')['ranking'], []);
check('newest season first', preborCompetitions($pdo)[0]['slug'], 'top-2027');
$rejected = fn(array $data, ?int $id = null) => (function () use ($pdo, $data, $id) {
    try {
        preborSaveCompetition($pdo, $data, $id);
        return false;
    } catch (InvalidArgumentException $e) {
        return true;
    }
})();
check('slug must be unique', $rejected(['slug' => 'top-2026'] + $next, null), true);
check('reserved slug', $rejected(['slug' => 'admin'] + $next, null), true);
check('best cannot exceed rounds', $rejected(['best' => 11] + $next, $next['id']), true);
check('editing keeps the slug', $rejected($next, $next['id']), false);

echo $failures ? "\n$failures FAILED\n" : "\nall passed\n";
exit($failures ? 1 : 0);
