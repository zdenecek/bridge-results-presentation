<?php

// php api/tests/results_import_test.php

require __DIR__ . '/../results_import.php';

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

function find(array $sections, string $title): ?array
{
    foreach ($sections as $section) {
        if ($section['title'] === $title) return $section;
        foreach ($section['children'] ?? [] as $child) {
            if ($child['title'] === $title) return $child;
        }
    }
    return null;
}

check('heading case from the sentences', resultsImportHeading('VELKÁ CENA ÚSTÍ NAD LABEM', ['ústí' => 'Ústí', 'labem' => 'Labem']), 'Velká cena Ústí nad Labem');
check('single letter at the end stays', [resultsImportHeading('SKUPINOVKA A', []), resultsImportHeading('MEMORIÁL ALENY A MARIA PETROVSKÝCH', [])],
    ['Skupinovka A', 'Memoriál Aleny a Maria Petrovských']);
check('players by capitals', resultsImportPlayers('Chris LAMBIE-HANSON - Ján TOMČÁNI'),
    [['first' => 'Chris', 'last' => 'Lambie-Hanson'], ['first' => 'Ján', 'last' => 'Tomčáni']]);
check('year from the tournament before', resultsImportDate(1, 9, null, 2026, '2025-26'), '2026-09-01');
check('written year outside the season is a typo', resultsImportDate(14, 4, 2025, null, '2025-26'), '2026-04-14');

// The current page
$slugs = ['skupinova-a-podzim-2026', 'skupinova-b-podzim-2026'];
$current = resultsParseListing(fixture('results/bkpraha-vysledky.html'), 'https://www.bkpraha.cz/vysledky/', '2026-27');
$plan = resultsImportPlan($current, '2026-27', $slugs, ['top-2026' => 'top-2026', 'imp-2026' => 'imp-2026'], 6);
check('vícekolové soutěže split into competitions', array_column($plan, 'title'), ['Pražská bridžová liga', 'Skupinovka A', 'Skupinovka B',
    'Topový přebor BKP', 'Impový přebor BKP', 'Malé švýcaráky', 'Párové turnaje Bavlnka', 'Bridžový týden Panonia', 'Velké ceny']);
$ska = find($plan, 'Skupinovka A');
check('skupinovka cycles inside it', array_map(fn($c) => [$c['title'], $c['kind'], $c['ref'], $c['note']], array_slice($ska['children'], 0, 3)), [
    ['Podzim', 'tournament', 'skupinova-a-podzim-2026', null],
    ['Zima', 'tournament', null, 'Soutěž začíná 30. 11. 2026.'],
    ['Jaro', 'tournament', null, 'Soutěž začíná 8. 2. 2027.'],
]);
check('přebory', array_map(fn($t) => [$t['kind'], $t['ref']], [find($plan, 'Topový přebor BKP'), find($plan, 'Impový přebor BKP')]),
    [['prebor', 'top-2026'], ['prebor', 'imp-2026']]);
$bav = find($plan, 'Párové turnaje Bavlnka');
check('bavlnka comes from its tables', [$bav['kind'], $bav['events']], ['bavlnka', []]);
$panonia = find($plan, 'Bridžový týden Panonia')['events'];
check('panonia parts', array_map(fn($e) => [$e['date'], $e['title']], array_slice($panonia, 2, 4)), [
    ['2026-09-09', 'Hlavní párový turnaj – 1. část'],
    ['2026-09-10', 'Hlavní párový turnaj – 2. část'],
    ['2026-09-10', 'Hlavní párový turnaj – celkoví vítězové'],
    ['2026-09-11', null],
]);
check('overall winners have no link', [$panonia[4]['url'], $panonia[4]['winner']['entries'][0]['players'][0]], [null, ['first' => 'Alexander', 'last' => 'Erde']]);
$znojmo = find($plan, 'Velká cena Znojma')['events'];
check('team with its line-up', [$znojmo[2]['winner']['kind'], $znojmo[2]['winner']['entries'][0]['name'], str_starts_with($znojmo[2]['winner']['entries'][0]['members'], 'Nikol VACHTARČÍKOVÁ')],
    ['team', 'KDO SE NA NĚJ KOUKNE', true]);
check('league without a winner on the page', find($plan, 'Pražská bridžová liga')['events'][0]['winner'], null);

// 2025/26: a full archive
$archive = resultsParseListing(fixture('results/bkpraha-archiv-2025-26.html'), 'https://www.bkpraha.cz/archiv-vysledku-2025-26/');
check('season from the page', $archive['season'], '2025-26');
$plan = resultsImportPlan($archive, '2025-26', ['skupinova-a-podzim-2025'], [], 0);
check('blocks of the archive', array_column($plan, 'title'), ['Pražská bridžová liga', 'Skupinovka A', 'Skupinovka B', 'Švýcarská skupinovka',
    'Malé švýcaráky', 'Velké ceny', 'Bridžový týden Panonia', 'Česko-německý turnaj', 'Narozeninový turnaj Blanky Medlínové',
    'Letní párové turnaje', 'Párové turnaje Bavlnka']);
$summer = find($plan, 'Pondělní cyklus')['events'];
check('summer dates without a year', array_column(array_slice($summer, 3, 2), 'date'), ['2026-07-27', '2026-08-03']);
check('tie at the top', count(end($summer)['winner']['entries']), 2);
$bav = find($plan, 'Párové turnaje Bavlnka');
check('bavlnka kept as a list when not stored', [$bav['kind'], count($bav['events'])], ['events', 34]);
check('year typo corrected', in_array('2026-04-14', array_column($bav['events'], 'date'), true), true);
// Only the autumn cycle is in this app here: it gets its own section, the other cycles stay listed winners.
$ska = array_values(array_filter($plan, fn($t) => $t['title'] === 'Skupinovka A'))[0];
check('mixed skupinovka', [array_column($ska['children'], 'title'), $ska['children'][0]['events'][0]['title']], [['Skupinovka A', 'Podzim'], 'Zima']);

// 2022/23: old Webnode pages
$old = resultsImportPlan(resultsParseListing(fixture('results/bkpraha-archiv-2022-23.html'), 'https://www.bkpraha.cz/archiv-vysledku-2022-23/'), '2022-23', [], [], 0);
check('links to old pages made absolute', find($old, 'Skupinovka A')['events'][0]['url'], 'https://www.bkpraha.cz/skupinovka-a-podzim-2022/');

// Into the database
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("CREATE TABLE tournaments (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, slug TEXT, data TEXT)");
$pdo->exec("INSERT INTO tournaments (name, slug, data) VALUES ('Skupinová A Podzim 2026', 'skupinova-a-podzim-2026', '{}')");
resultsEnsureSchema($pdo);
$web = [
    'https://www.bkpraha.cz/vysledky/' => fixture('results/bkpraha-vysledky.html'),
    'https://vysledky.bkpraha.cz/prezentace/2026/prazska-bridzova-liga/' => fixture('results/pbl-page.html'),
    'https://vysledky.bkpraha.cz/prezentace/2026/prazska-bridzova-liga/results.json.gz' => fixture('results/pbl-results.json.gz'),
];
$http = fn(string $url) => isset($web[$url]) ? [$web[$url], $url, 200] : ['', $url, 404];
$report = resultsImport($pdo, '2026-27', 'https://www.bkpraha.cz/vysledky/', $http);
// The league and Skupinovka B, which is not in this test database, are named without a winner.
check('import counts', [$report['sections'], $report['fetched'], $report['notFetched']], [17, 2, 0]);
$again = resultsImport($pdo, '2026-27', 'https://www.bkpraha.cz/vysledky/', $http, 45, true);
check('loading again replaces the season', [$again['sections'], count(resultsPage($pdo, '2026-27')['sections'])], [17, 9]);
$page = resultsPage($pdo, '2026-27', true);
$pbl = $page['sections'][0]['events'][0];
check('league read from its results', [$pbl['winner']['entries'][0]['name'], $pbl['status']], ['Pondělní Beerek', 'ok']);
check('skupinovka not in this app is a list', [$page['sections'][2]['title'], $page['sections'][2]['kind']], ['Skupinovka B', 'events']);
$rejected = false;
try {
    resultsImport($pdo, '2026-27', 'https://www.bkpraha.cz/vysledky/', $http);
} catch (InvalidArgumentException $e) {
    $rejected = true;
}
check('only into an empty season', $rejected, true);

echo $failures ? "\n$failures FAILED\n" : "\nall passed\n";
exit($failures ? 1 : 0);
