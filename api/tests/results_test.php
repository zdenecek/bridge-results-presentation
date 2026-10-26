<?php

// php api/tests/results_test.php

require __DIR__ . '/../results.php';

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

function fixture(string $path): string
{
    return file_get_contents(__DIR__ . '/fixtures/' . $path);
}

function rejected(callable $action): bool
{
    try {
        $action();
        return false;
    } catch (InvalidArgumentException $e) {
        return true;
    }
}

check('season starts in September', [resultsSeasonOf('2026-09-01'), resultsSeasonOf('2027-08-31'), resultsSeasonOf('2026-08-31')],
    ['2026-27', '2026-27', '2025-26']);

$web = [
    'https://vysledky.bkpraha.cz/prezentace/2026/prazska-bridzova-liga/' => fixture('results/pbl-page.html'),
    'https://vysledky.bkpraha.cz/prezentace/2026/prazska-bridzova-liga/results.json.gz' => fixture('results/pbl-results.json.gz'),
    'https://bridge.zdenektomis.eu/vysledky/2026/uvodni-svycar/0914/' => fixture('bavlnka/tc-page.html'),
    'https://bridge.zdenektomis.eu/vysledky/2026/uvodni-svycar/0914/results.json.gz' => gzencode(fixture('bavlnka/tc-results.json')),
    'https://www.bridgebase.6f.sk/results/ZF/2026/260922/result.php?round_number=1' => fixture('bavlnka/giborg.html'),
    'https://example.com/vc-znojmo' => '<html><body>Nějaká jiná stránka</body></html>',
];
$http = function (string $url) use ($web): array {
    return isset($web[$url]) ? [$web[$url], $url, 200] : ['Not found', $url, 404];
};

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("CREATE TABLE tournaments (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, slug TEXT, data TEXT)");
$pdo->exec("INSERT INTO tournaments (name, slug, data) VALUES ('Skupinová A Podzim 2026', 'skupinova-a-podzim-2026',
    '{\"totalRounds\": 5, \"rounds\": {\"1\": {\"boardResults\": [1]}, \"2\": {\"boardResults\": [1]}, \"3\": {\"boardResults\": []}}}')");
resultsEnsureSchema($pdo);

// Structure
$season = '2026-27';
$multi = resultsSaveSection($pdo, ['season' => $season, 'title' => 'Vícekolové soutěže', 'kind' => 'group']);
$pbl = resultsSaveSection($pdo, ['season' => $season, 'parent_id' => $multi, 'title' => 'Pražská bridžová liga', 'kind' => 'events']);
$ska = resultsSaveSection($pdo, ['season' => $season, 'parent_id' => $multi, 'title' => 'Skupinovka A', 'kind' => 'group']);
$small = resultsSaveSection($pdo, ['season' => $season, 'title' => 'Malé švýcaráky', 'kind' => 'events']);
$bavlnka = resultsSaveSection($pdo, ['season' => $season, 'title' => 'Párové turnaje Bavlnka', 'kind' => 'bavlnka']);
check('only two levels', rejected(fn() => resultsSaveSection($pdo, ['season' => $season, 'parent_id' => $ska, 'title' => 'Podzim', 'kind' => 'tournament', 'ref' => 'x'])), true);
$podzim = resultsSaveSection($pdo, ['season' => $season, 'parent_id' => $multi, 'title' => 'Skupinovka A – podzim', 'kind' => 'tournament', 'ref' => 'skupinova-a-podzim-2026']);
$zima = resultsSaveSection($pdo, ['season' => $season, 'parent_id' => $multi, 'title' => 'Skupinovka A – zima', 'kind' => 'tournament', 'note' => 'Soutěž začíná 30. 11. 2026.']);
check('a skupinovka needs a tournament or a note', rejected(fn() => resultsSaveSection($pdo, ['season' => $season, 'title' => 'X', 'kind' => 'tournament'])), true);
$top = resultsSaveSection($pdo, ['season' => $season, 'parent_id' => $multi, 'title' => 'Topový přebor BKP', 'kind' => 'prebor', 'ref' => 'top-2026']);
resultsDeleteSection($pdo, $ska);

// Events: a TC team event, a TC pairs event, Giborg, a page we can't read
$team = resultsAddEvent($pdo, $pbl, ['url' => 'https://vysledky.bkpraha.cz/prezentace/2026/prazska-bridzova-liga/', 'date' => '2026-10-12', 'ongoing' => true], $http);
$pairs = resultsAddEvent($pdo, $small, ['url' => 'https://bridge.zdenektomis.eu/vysledky/2026/uvodni-svycar/0914/', 'title' => 'Memoriál Tomáše Etlíka'], $http);
$giborg = resultsAddEvent($pdo, $small, ['url' => 'https://www.bridgebase.6f.sk/results/ZF/2026/260922/result.php?round_number=1'], $http);
$unknown = resultsAddEvent($pdo, $small, ['url' => 'https://example.com/vc-znojmo', 'date' => '2026-09-19'], $http);
$missing = resultsAddEvent($pdo, $small, ['url' => 'https://example.com/404', 'date' => '2026-09-20'], $http);
check('statuses', array_column([$team, $pairs, $giborg, $unknown, $missing], 'status'), ['ok', 'ok', 'ok', 'link', 'error']);
check('events only in event sections', rejected(fn() => resultsAddEvent($pdo, $bavlnka, ['url' => 'https://example.com/'], $http)), true);
resultsUpdateEvent($pdo, $unknown['id'], ['date' => '2026-09-19', 'winner_override' => 'Ján TOMČÁNI - Juraj KVOČEK']);
resultsDeleteEvent($pdo, $missing['id']);

// Bavlnka comes from its own tables
bavlnkaEnsureSchema($pdo);
$pdo->exec("INSERT INTO bavlnka_tournaments (id, source_url, results_url, date, pairs, status) VALUES
    (1, 'a', 'https://bridge.zdenektomis.eu/vysledky/2026/bkp-bavlnka/0915bavlnka/', '2026-09-15', 2, 'ok'),
    (2, 'b', NULL, '2026-06-02', 0, 'ok'),
    (3, 'c', NULL, '2026-09-29', 0, 'ignored')");
$pdo->exec("INSERT INTO bavlnka_results (tournament_id, pair_number, rank_from, rank_to, player) VALUES
    (1, 4, 1, 1, 'Věrtelářová Anna'), (1, 4, 1, 1, 'Fišerová Milada'), (1, 2, 2, 2, 'Malý Radek')");

$page = resultsPage($pdo, $season, false, '2026-10-21');
check('top level order', array_column($page['sections'], 'title'), ['Vícekolové soutěže', 'Malé švýcaráky', 'Párové turnaje Bavlnka']);
$children = $page['sections'][0]['children'];
check('children order', array_column($children, 'title'), ['Pražská bridžová liga', 'Skupinovka A – podzim', 'Skupinovka A – zima', 'Topový přebor BKP']);
check('team winner', $children[0]['events'][0]['winner'], ['kind' => 'team', 'entries' => [['name' => 'Pondělní Beerek']]]);
check('league still running', [$children[0]['events'][0]['ongoing'], $page['sections'][1]['events'][0]['ongoing']], [true, false]);
check('tournament section', [$children[1]['tournament']['slug'], $children[2]['tournament'], $children[2]['note']],
    ['skupinova-a-podzim-2026', null, 'Soutěž začíná 30. 11. 2026.']);
check('přebor without rounds', $children[3]['prebor']['lastRound'], null);
check('what is being played', [$page['sections'][0]['state'], array_column($children, 'state'), $page['sections'][2]['state']],
    ['running', ['running', 'running', 'upcoming', 'upcoming'], 'running']);
check('skupinovka standing', [$children[1]['tournament']['played'], $children[1]['tournament']['rounds']], [2, 5]);
check('a past season is over', resultsPage($pdo, $season, false, '2028-01-01')['sections'][2]['state'], 'done');

$small = $page['sections'][1]['events'];
check('events by date, from the folder when not given', array_column($small, 'date'), ['2026-09-14', '2026-09-19', '2026-09-22']);
check('tc winner', [$small[0]['title'], $small[0]['winner']['entries'][0]['players']],
    ['Memoriál Tomáše Etlíka', [['first' => 'Adam', 'last' => 'Olmer'], ['first' => 'Bela', 'last' => 'Lemmon']]]);
check('override wins', $small[1]['winner'], ['kind' => 'text', 'text' => 'Ján TOMČÁNI - Juraj KVOČEK']);
check('giborg winner', $small[2]['winner']['entries'][0]['players'], [['first' => 'Stanislava', 'last' => 'Lálová'], ['first' => 'Jan', 'last' => 'Chlouba']]);
check('public page hides status', array_key_exists('status', $small[0]), false);
check('admin page shows status', resultsPage($pdo, $season, true)['sections'][1]['events'][1]['status'], 'link');

$bav = $page['sections'][2]['events'];
check('bavlnka of this season only', array_column($bav, 'date'), ['2026-09-15']);
check('bavlnka winner', $bav[0]['winner']['entries'], [['players' => [['first' => 'Anna', 'last' => 'Věrtelářová'], ['first' => 'Milada', 'last' => 'Fišerová']]]]);
$leaders = $page['sections'][2]['leaders'];
check('bavlnka leaders, ties included', [$leaders['updated'], array_map(fn($p) => [$p['rank'], $p['last'], $p['points']], $leaders['players'])],
    ['2026-09-15', [[1, 'Fišerová', 30], [1, 'Věrtelářová', 30], [3, 'Malý', 10]]]);
check('no leaders on another season', resultsPage($pdo, $season, false, '2027-09-21')['sections'][2]['leaders'], null);

// Přebor with a round
$pdo->exec("INSERT INTO prebor_rounds (competition_id, date, url, source, pairs, status) VALUES
    ((SELECT id FROM prebor_competitions WHERE slug = 'top-2026'), '2026-09-16', 'https://vysledky.bkpraha.cz/prezentace/2026/top-prebor/', 'tc', 15, 'ok')");
$pdo->exec("INSERT INTO prebor_results (round_id, pair_number, rank_from, rank_to, score, title, players) VALUES
    (1, 5, 1, 1, 62.82, 'Mikuláš Krása - Vojtěch Franz', '[\"Krása Mikuláš\", \"Franz Vojtěch\"]')");
$round = resultsPage($pdo, $season)['sections'][0]['children'][3]['prebor']['lastRound'];
check('přebor last round winner', [$round['number'], $round['winner']['entries'][0]['players'][1]], [1, ['first' => 'Vojtěch', 'last' => 'Franz']]);

// The daily refresh: running competitions and never-read results of the current season only
$refreshed = [];
$counting = function (string $url) use ($http, &$refreshed) {
    $refreshed[] = $url;
    return $http($url);
};
$pdo->exec("INSERT INTO results_events (section_id, date, url, status)
    SELECT id, '2026-10-20', 'https://example.com/404', 'pending' FROM results_sections WHERE title = 'Malé švýcaráky' AND season = '2026-27'");
$report = resultsRefreshRunning($pdo, $counting, '2026-10-27');
check('refresh reads the league and the pending one', [array_column($report, 'status'), count(array_unique($refreshed))], [['ok', 'error'], 3]);
check('refresh only in the current season', resultsRefreshRunning($pdo, $counting, '2027-10-27'), []);

// Ordering and the next season
resultsMoveSection($pdo, $bavlnka, -1);
resultsMoveSection($pdo, $multi, -1);
check('move', array_column(resultsPage($pdo, $season)['sections'], 'title'), ['Vícekolové soutěže', 'Párové turnaje Bavlnka', 'Malé švýcaráky']);
check('non-empty section stays', rejected(fn() => resultsDeleteSection($pdo, $multi)), true);
check('copied sections', resultsCopySeason($pdo, $season, '2027-28'), 7);
$next = resultsPage($pdo, '2027-28')['sections'];
check('copy keeps structure without events', [array_column($next, 'title'), $next[2]['events']],
    [['Vícekolové soutěže', 'Párové turnaje Bavlnka', 'Malé švýcaráky'], []]);
check('copied přebor waits for a new one', array_column($next[0]['children'], 'kind'), ['events', 'events', 'events', 'events']);
check('seasons newest first', resultsSeasons($pdo), ['2027-28', '2026-27']);
check('season copied once', rejected(fn() => resultsCopySeason($pdo, $season, '2027-28')), true);

echo $failures ? "\n$failures FAILED\n" : "\nall passed\n";
exit($failures ? 1 : 0);
