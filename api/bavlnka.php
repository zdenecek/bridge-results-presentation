<?php

const BAVLNKA_DEFAULTS = [
    // Tournament Calculator exports are listed on an index page per year; Giborg results have
    // no listing, so the update tries the address each recent Tuesday's results would have.
    'tcIndex' => 'https://bridge.zdenektomis.eu/vysledky/{year}/bkp-bavlnka/',
    'giborgUrl' => 'https://www.bridgebase.6f.sk/results/ZF/{year}/{yymmdd}/result.php?round_number=1',
    'weekday' => 2,
    'discoverWeeks' => 6,
    'window' => 50,
    'blockSize' => 5,
    'blockDecay' => 0.1,
    'participationPoints' => 10,
    'pointsPerPairBeaten' => 20,
    'timeBudget' => 60,
    'maxAttempts' => 3,
];

// Spellings Ondřej Krása merged by hand in the original Google Sheet ranking.
const BAVLNKA_SEED_ALIASES = [
    'Mowell Ken' => 'Mowll Kenneth',
    'Mowll Ken' => 'Mowll Kenneth',
    'Russel Duncan' => 'Russell Duncan',
    'Kabelková Minika' => 'Kabelková Monika',
    'Čabrielová Danuše' => 'Čabrilová Danuše',
    'Navrátil Ian' => 'Navrátil Jan',
    'Dušková Zlata' => 'Dušková Světla',
    'Lencová' => 'Lencová Eva',
    'Herstusová' => 'Herstusová Kateřina',
    'Jungová Kateřina' => 'Herstusová Kateřina',
];

function bavlnkaConfig(array $config): array
{
    return array_merge(BAVLNKA_DEFAULTS, $config['bavlnka'] ?? []);
}

/** Key for the scheduled update URL, so the admin password never appears in URLs or logs. */
function bavlnkaCronKey(string $apikey): string
{
    return substr(hash_hmac('sha256', 'bavlnka-update', $apikey), 0, 24);
}

function bavlnkaEnsureSchema(PDO $pdo): void
{
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS bavlnka_tournaments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            source_url VARCHAR(255) NOT NULL UNIQUE,
            results_url VARCHAR(500),
            listed_date DATE,
            date DATE,
            format VARCHAR(16),
            title VARCHAR(255),
            pairs INT,
            status VARCHAR(16) NOT NULL DEFAULT 'pending',
            error TEXT,
            attempts INT NOT NULL DEFAULT 0,
            fetched_at DATETIME
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS bavlnka_results (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tournament_id INT NOT NULL,
            pair_number INT,
            rank_from INT NOT NULL,
            rank_to INT NOT NULL,
            score DECIMAL(8,2),
            player VARCHAR(150) NOT NULL,
            cbs_id INT
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS bavlnka_aliases (
            alias VARCHAR(150) NOT NULL PRIMARY KEY,
            name VARCHAR(150) NOT NULL
        )");
        $insert = $pdo->prepare("INSERT OR IGNORE INTO bavlnka_aliases (alias, name) VALUES (?, ?)");
    } else {
        $pdo->exec("SET NAMES utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS bavlnka_tournaments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            source_url VARCHAR(255) NOT NULL UNIQUE,
            results_url VARCHAR(500) NULL,
            listed_date DATE NULL,
            date DATE NULL,
            format VARCHAR(16) NULL,
            title VARCHAR(255) NULL,
            pairs INT NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'pending',
            error TEXT NULL,
            attempts INT NOT NULL DEFAULT 0,
            fetched_at DATETIME NULL
        ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
        $pdo->exec("CREATE TABLE IF NOT EXISTS bavlnka_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tournament_id INT NOT NULL,
            pair_number INT NULL,
            rank_from INT NOT NULL,
            rank_to INT NOT NULL,
            score DECIMAL(8,2) NULL,
            player VARCHAR(150) NOT NULL,
            cbs_id INT NULL,
            INDEX (tournament_id)
        ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
        $pdo->exec("CREATE TABLE IF NOT EXISTS bavlnka_aliases (
            alias VARCHAR(150) NOT NULL PRIMARY KEY,
            name VARCHAR(150) NOT NULL
        ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
        $insert = $pdo->prepare("INSERT IGNORE INTO bavlnka_aliases (alias, name) VALUES (?, ?)");
    }

    if ((int) $pdo->query("SELECT COUNT(*) FROM bavlnka_aliases")->fetchColumn() === 0) {
        foreach (BAVLNKA_SEED_ALIASES as $alias => $name) {
            $insert->execute([$alias, $name]);
        }
    }
}

/** @return array{0: string, 1: string, 2: int} body, final URL after redirects, HTTP status */
function bavlnkaHttpGet(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'vysledky.bkpraha.cz (zebricek Bavlnky)',
        CURLOPT_ENCODING => '',
    ]);
    $body = curl_exec($ch);
    if ($body === false) {
        throw new RuntimeException("Nepodařilo se stáhnout $url: " . curl_error($ch));
    }
    return [$body, curl_getinfo($ch, CURLINFO_EFFECTIVE_URL), (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE)];
}

function bavlnkaCleanText(string $text): string
{
    return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $text));
}

/** Drops placeholder parts like the "." typed for a guest's unknown first name. */
function bavlnkaPlayerName(string $name): string
{
    $parts = array_filter(explode(' ', bavlnkaCleanText($name)), fn($part) => preg_match('/\p{L}/u', $part));
    return implode(' ', $parts);
}

/**
 * Splits "Příjmení Jméno" as Giborg writes it: the last word is the first name, a single word is a
 * surname (guests often have only that). Multi-word first names come out wrong, which is rare.
 *
 * @return array{first: string, last: string}
 */
function bavlnkaSplitName(string $name): array
{
    $parts = explode(' ', bavlnkaCleanText($name));
    $first = count($parts) > 1 ? array_pop($parts) : '';
    return ['first' => $first, 'last' => implode(' ', $parts)];
}

function bavlnkaDate(string $day, string $month, string $year): ?string
{
    return checkdate((int) $month, (int) $day, (int) $year)
        ? sprintf('%04d-%02d-%02d', $year, $month, $day)
        : null;
}

function bavlnkaLoadHtml(string $html): DOMXPath
{
    $doc = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    return new DOMXPath($doc);
}

function bavlnkaAbsoluteUrl(string $href, string $base): string
{
    if (preg_match('#^https?://#i', $href)) return $href;
    $parts = parse_url($base);
    $origin = $parts['scheme'] . '://' . $parts['host'];
    if (str_starts_with($href, '//')) return $parts['scheme'] . ':' . $href;
    if (str_starts_with($href, '/')) return $origin . $href;
    $dir = preg_replace('#/[^/]*$#', '/', $parts['path'] ?? '/');
    return $origin . $dir . $href;
}

/**
 * Reads the "Párové turnaje Bavlnka" section of a bkpraha.cz results page, which is how the
 * tournaments were found before that page moved here.
 * Each tournament there is a paragraph with the date it was played followed by a "Výsledky" link.
 *
 * @return list<array{date: ?string, url: string}>
 */
function bavlnkaParseListing(string $html, string $pageUrl): array
{
    $xp = bavlnkaLoadHtml($html);
    $entries = [];
    $inSection = false;
    $date = null;

    foreach ($xp->query('//h1 | //p | //a') as $node) {
        $text = bavlnkaCleanText($node->textContent);
        if ($node->nodeName === 'h1') {
            $inSection = preg_match('/bavln/iu', $text) && !preg_match('/žebř|pořad/iu', $text);
            $date = null;
            continue;
        }
        if (!$inSection) continue;

        if ($node->nodeName === 'p') {
            if ($xp->query('.//a', $node)->length === 0
                && preg_match('/(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})/u', $text, $m)) {
                $date = bavlnkaDate($m[1], $m[2], $m[3]);
            }
            continue;
        }

        $href = trim($node->getAttribute('href'));
        if ($href === '' || str_starts_with($href, '#') || preg_match('/^(mailto|javascript):/i', $href)) continue;
        $entries[] = ['date' => $date, 'url' => bavlnkaAbsoluteUrl($href, $pageUrl)];
        $date = null;
    }

    return $entries;
}

/**
 * Reads the index of one year's Tournament Calculator exports (links like ./1013bavlnka/).
 *
 * @return list<array{date: ?string, url: string}>
 */
function bavlnkaParseTcIndex(string $html, string $indexUrl, int $year): array
{
    preg_match_all('#href="\.?/?(\d{2})(\d{2})bavlnka/?"#i', $html, $matches, PREG_SET_ORDER);
    $entries = [];
    foreach ($matches as [, $month, $day]) {
        $date = bavlnkaDate($day, $month, (string) $year);
        if ($date) $entries[] = ['date' => $date, 'url' => rtrim($indexUrl, '/') . "/$month{$day}bavlnka/"];
    }
    return $entries;
}

/**
 * Finds tournaments played in the last discoverWeeks weeks that are not stored yet. Older ones are
 * left alone on purpose: adding a forgotten tournament would change a ranking people already know,
 * so that is done by hand in the admin.
 *
 * @return array{0: list<array{date: string, url: string}>, 1: list<array>} entries, errors
 */
function bavlnkaDiscover(PDO $pdo, array $cfg, callable $http, string $today): array
{
    $since = date('Y-m-d', strtotime("$today -{$cfg['discoverWeeks']} weeks"));
    $known = array_flip($pdo->query("SELECT COALESCE(date, listed_date) FROM bavlnka_tournaments
        WHERE COALESCE(date, listed_date) IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN));
    $found = [];
    $errors = [];

    foreach (array_unique([(int) substr($since, 0, 4), (int) substr($today, 0, 4)]) as $year) {
        $indexUrl = str_replace('{year}', (string) $year, $cfg['tcIndex']);
        try {
            [$body, , $status] = $http($indexUrl);
            if ($status === 404) continue;
            if ($status >= 400) throw new RuntimeException("HTTP $status");
            foreach (bavlnkaParseTcIndex($body, $indexUrl, $year) as $entry) {
                if ($entry['date'] >= $since && $entry['date'] <= $today && !isset($known[$entry['date']])) {
                    $found[$entry['date']] = $entry;
                }
            }
        } catch (Throwable $e) {
            $errors[] = ['source' => $indexUrl, 'error' => $e->getMessage()];
        }
    }

    for ($day = strtotime($since); $day <= strtotime($today); $day = strtotime('+1 day', $day)) {
        $date = date('Y-m-d', $day);
        if ((int) date('N', $day) !== $cfg['weekday'] || isset($known[$date]) || isset($found[$date])) continue;
        $url = str_replace(['{year}', '{yymmdd}'], [date('Y', $day), date('ymd', $day)], $cfg['giborgUrl']);
        try {
            [$body, , $status] = $http($url);
            if ($status === 200 && stripos($body, 'Bavln') !== false) {
                $found[$date] = ['date' => $date, 'url' => $url];
            }
        } catch (Throwable $e) {
            $errors[] = ['source' => $url, 'error' => $e->getMessage()];
        }
    }

    krsort($found);
    return [array_values($found), $errors];
}

function bavlnkaStripUrl(string $url): string
{
    return preg_replace('/[?#].*$/', '', $url);
}

/**
 * Downloads one tournament's results. Bavlnka results are published either from
 * Tournament Calculator (JSON data next to the page) or from Giborg/GoodLuck (an HTML table).
 *
 * @return array{format: string, resultsUrl: string, title: ?string, date: ?string, pairs: list<array>}
 */
function bavlnkaFetchResults(string $url, callable $http): array
{
    [$body, $finalUrl, $status] = $http(preg_replace('/#.*$/', '', $url));
    if ($status >= 400) {
        throw new RuntimeException("Stránka s výsledky vrátila HTTP $status");
    }

    if (stripos($finalUrl, '6f.sk/') !== false || stripos($body, 'Giborg') !== false) {
        $parsed = bavlnkaParseGiborg($body);
        $parsed['format'] = 'giborg';
    } elseif (stripos($body, 'Tournament Calculator') !== false) {
        $parsed = bavlnkaFetchTournamentCalculator($finalUrl, $body, $http);
        $parsed['format'] = 'tc';
    } else {
        throw new RuntimeException('Neznámý formát výsledků');
    }

    if (!$parsed['pairs']) {
        throw new RuntimeException('Ve výsledcích nejsou žádné dvojice');
    }
    $parsed['resultsUrl'] = $finalUrl;
    return $parsed;
}

function bavlnkaFetchTournamentCalculator(string $pageUrl, string $html, callable $http): array
{
    $base = bavlnkaStripUrl($pageUrl);
    if (!str_ends_with($base, '/')) {
        $base = preg_match('#/[^/]+\.[a-z0-9]+$#i', $base) ? preg_replace('#[^/]+$#', '', $base) : $base . '/';
    }

    $data = null;
    foreach (['results.json.gz', 'results.json'] as $file) {
        [$body, , $status] = $http($base . $file);
        if ($status !== 200) continue;
        if (str_starts_with($body, "\x1f\x8b")) {
            $body = gzdecode($body);
        }
        $body = preg_replace('/^\xEF\xBB\xBF/', '', (string) $body);
        $data = json_decode($body, true);
        if (is_array($data)) break;
    }
    if (!is_array($data) || !isset($data['Results'])) {
        throw new RuntimeException('Tournament Calculator: chybí results.json');
    }

    $title = null;
    if (preg_match('#<title>(.*?)</title>#is', $html, $m)) {
        $title = bavlnkaCleanText(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    // The page title is typed by hand and sometimes wrong, the folder name (…/2026/…/1013bavlnka/) is not.
    $date = null;
    if (preg_match('#/(20\d\d)/(?:[^/]+/)*?(\d\d)(\d\d)[a-z]*/?$#i', $base, $m)) {
        $date = bavlnkaDate($m[3], $m[2], $m[1]);
    }

    return [
        'title' => $title,
        'date' => $date,
        'pairs' => bavlnkaParseTournamentCalculator($data),
    ];
}

function bavlnkaParseTournamentCalculator(array $data): array
{
    $tied = array_count_values(array_map(fn($r) => (int) $r['Place'], $data['Results']));

    $pairs = [];
    foreach ($data['Results'] as $result) {
        $participant = $result['Participant'] ?? [];
        $personKeys = array_values(array_filter(array_keys($participant), fn($k) => preg_match('/^_person\d+$/', $k)));
        sort($personKeys, SORT_NATURAL);

        $players = [];
        foreach ($personKeys as $key) {
            $person = $participant[$key];
            if (!is_array($person)) continue;
            $name = bavlnkaPlayerName(($person['_lastName'] ?? '') . ' ' . ($person['_firstName'] ?? ''));
            if ($name === '') continue;
            $pid = $person['_pid'] ?? null;
            $isRegistered = is_array($pid) && str_starts_with((string) ($pid['__type'] ?? ''), 'RealPid:');
            $players[] = [
                'name' => $name,
                'cbsId' => $isRegistered ? (int) $pid['Number'] : null,
                'first' => bavlnkaPlayerName((string) ($person['_firstName'] ?? '')),
                'last' => bavlnkaPlayerName((string) ($person['_lastName'] ?? '')),
            ];
        }

        $place = (int) $result['Place'];
        $score = $result['Result']['_pointsDecimal'] ?? null;
        $pairs[] = [
            'pair' => isset($participant['Number']) ? (int) $participant['Number'] : null,
            'title' => isset($participant['_name']) ? bavlnkaCleanText((string) $participant['_name']) : null,
            'rankFrom' => $place,
            'rankTo' => $place + $tied[$place] - 1,
            'score' => is_numeric($score) ? round((float) $score, 2) : null,
            'players' => $players,
        ];
    }
    return $pairs;
}

function bavlnkaParseGiborg(string $html): array
{
    $xp = bavlnkaLoadHtml($html);
    $text = bavlnkaCleanText($xp->document->documentElement->textContent ?? '');

    $date = null;
    if (preg_match('/•\s*(\d{1,2})\.(\d{1,2})\.(\d{4})\s*•/u', $text, $m)
        || preg_match('/(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})/u', $text, $m)) {
        $date = bavlnkaDate($m[1], $m[2], $m[3]);
    }
    $title = preg_match('/^(.*?)\s*•/u', $text, $m) ? $m[1] : null;

    $columns = null;
    $pairs = [];
    foreach ($xp->query('//tr') as $row) {
        $cells = [];
        foreach ($row->childNodes as $cell) {
            if (in_array($cell->nodeName, ['td', 'th'], true)) {
                $cells[] = bavlnkaCleanText($cell->textContent);
            }
        }

        if ($columns === null) {
            $columns = bavlnkaGiborgColumns($cells);
            continue;
        }

        $rank = $cells[$columns['rank']] ?? '';
        $names = $cells[$columns['players']] ?? '';
        if (!preg_match('/^(\d+)(?:\s*-\s*(\d+))?\.?$/', $rank, $m) || $names === '') continue;

        $players = [];
        foreach (preg_split('/\s+-\s+/u', $names) as $name) {
            $name = bavlnkaPlayerName($name);
            if ($name !== '') $players[] = ['name' => $name, 'cbsId' => null] + bavlnkaSplitName($name);
        }

        $score = isset($columns['score']) ? str_replace(',', '.', $cells[$columns['score']] ?? '') : '';
        $pair = isset($columns['pair']) ? ($cells[$columns['pair']] ?? '') : '';
        $pairs[] = [
            'pair' => ctype_digit($pair) ? (int) $pair : null,
            'rankFrom' => (int) $m[1],
            'rankTo' => isset($m[2]) && $m[2] !== '' ? (int) $m[2] : (int) $m[1],
            'score' => is_numeric($score) ? (float) $score : null,
            'players' => $players,
        ];
    }

    return ['title' => $title, 'date' => $date, 'pairs' => $pairs];
}

/** Finds the header row of the ranking table and returns its column positions. */
function bavlnkaGiborgColumns(array $cells): ?array
{
    $columns = [];
    foreach ($cells as $i => $cell) {
        $label = mb_strtolower($cell);
        if (in_array($label, ['rank', 'pořadí', 'poradie', 'místo'], true)) $columns['rank'] = $i;
        elseif (in_array($label, ['players', 'hráči', 'dvojice'], true)) $columns['players'] = $i;
        elseif (in_array($label, ['pair', 'pár', 'č.'], true)) $columns['pair'] = $i;
        elseif (str_contains($label, '%')) $columns['score'] = $i;
    }
    return isset($columns['rank'], $columns['players']) ? $columns : null;
}

function bavlnkaInsertTournament(PDO $pdo, string $url, ?string $date): bool
{
    $exists = $pdo->prepare("SELECT 1 FROM bavlnka_tournaments WHERE source_url = ?");
    $exists->execute([$url]);
    if ($exists->fetchColumn()) return false;

    $pdo->prepare("INSERT INTO bavlnka_tournaments (source_url, listed_date, date, status) VALUES (?, ?, ?, 'pending')")
        ->execute([$url, $date, $date]);
    return true;
}

function bavlnkaProcessTournament(PDO $pdo, array $tournament, callable $http): array
{
    $parsed = bavlnkaFetchResults($tournament['source_url'], $http);
    $date = $parsed['date'] ?? $tournament['listed_date'];
    if (!$date) {
        throw new RuntimeException('Neznámé datum turnaje');
    }

    $duplicate = $pdo->prepare("SELECT id FROM bavlnka_tournaments WHERE date = ? AND status = 'ok' AND id <> ?");
    $duplicate->execute([$date, $tournament['id']]);
    $status = $duplicate->fetchColumn() ? 'duplicate' : 'ok';

    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM bavlnka_results WHERE tournament_id = ?")->execute([$tournament['id']]);
        $insert = $pdo->prepare("INSERT INTO bavlnka_results
            (tournament_id, pair_number, rank_from, rank_to, score, player, cbs_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($parsed['pairs'] as $pair) {
            foreach ($pair['players'] as $player) {
                $insert->execute([$tournament['id'], $pair['pair'], $pair['rankFrom'], $pair['rankTo'],
                    $pair['score'], $player['name'], $player['cbsId']]);
            }
        }
        $pdo->prepare("UPDATE bavlnka_tournaments SET results_url = ?, date = ?, format = ?, title = ?, pairs = ?,
            status = ?, error = NULL, fetched_at = ? WHERE id = ?")
            ->execute([$parsed['resultsUrl'], $date, $parsed['format'], $parsed['title'], count($parsed['pairs']),
                $status, date('Y-m-d H:i:s'), $tournament['id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return ['id' => (int) $tournament['id'], 'date' => $date, 'status' => $status, 'pairs' => count($parsed['pairs'])];
}

/**
 * Finds tournaments played recently and downloads results of those not stored yet,
 * newest first, until the time budget runs out. Run it repeatedly to catch up.
 */
function bavlnkaUpdate(PDO $pdo, array $config, ?callable $http = null, ?string $today = null): array
{
    $http ??= 'bavlnkaHttpGet';
    $cfg = bavlnkaConfig($config);
    bavlnkaEnsureSchema($pdo);
    $start = microtime(true);
    $report = ['discovered' => [], 'processed' => [], 'errors' => []];

    [$entries, $report['errors']] = bavlnkaDiscover($pdo, $cfg, $http, $today ?? date('Y-m-d'));
    foreach ($entries as $entry) {
        if (bavlnkaInsertTournament($pdo, $entry['url'], $entry['date'])) {
            $report['discovered'][] = $entry;
        }
    }

    $todo = $pdo->prepare("SELECT * FROM bavlnka_tournaments
        WHERE status = 'pending' OR (status = 'error' AND attempts < ?)
        ORDER BY listed_date DESC, id DESC");
    $todo->execute([$cfg['maxAttempts']]);
    $failed = $pdo->prepare("UPDATE bavlnka_tournaments SET status = 'error', error = ?, attempts = attempts + 1,
        fetched_at = ? WHERE id = ?");

    foreach ($todo->fetchAll(PDO::FETCH_ASSOC) as $tournament) {
        if (microtime(true) - $start > $cfg['timeBudget']) break;
        try {
            $report['processed'][] = bavlnkaProcessTournament($pdo, $tournament, $http);
        } catch (Throwable $e) {
            $failed->execute([$e->getMessage(), date('Y-m-d H:i:s'), $tournament['id']]);
            $report['errors'][] = ['id' => (int) $tournament['id'], 'url' => $tournament['source_url'], 'error' => $e->getMessage()];
        }
    }

    $remaining = $pdo->prepare("SELECT COUNT(*) FROM bavlnka_tournaments WHERE status = 'pending' OR (status = 'error' AND attempts < ?)");
    $remaining->execute([$cfg['maxAttempts']]);
    $report['remaining'] = (int) $remaining->fetchColumn();
    return $report;
}

function bavlnkaFold(string $name): string
{
    $name = bavlnkaCleanText($name);
    if (class_exists('Normalizer')) {
        $name = preg_replace('/\p{Mn}+/u', '', Normalizer::normalize($name, Normalizer::FORM_D));
    }
    return mb_strtolower($name);
}

/**
 * Maps every spelling of a name to one player: aliases first, then names that differ only
 * in case or diacritics, then a bare surname to the only full name with that surname.
 *
 * @param list<array{player: string, date: string}> $rows
 * @return array{0: callable(string): string, 1: array<string, string>} resolver to a player key, key => display name
 */
function bavlnkaNameResolver(array $rows, array $aliases): array
{
    $aliasByFold = [];
    foreach ($aliases as $alias => $name) {
        $aliasByFold[bavlnkaFold($alias)] = bavlnkaCleanText($name);
    }
    $applyAlias = fn(string $name) => $aliasByFold[bavlnkaFold($name)] ?? bavlnkaCleanText($name);

    $bySurname = [];
    foreach ($rows as $row) {
        $key = bavlnkaFold($applyAlias($row['player']));
        if (str_contains($key, ' ')) {
            $bySurname[strtok($key, ' ')][$key] = true;
        }
    }

    $resolve = function (string $name) use ($applyAlias, $bySurname): string {
        $key = bavlnkaFold($applyAlias($name));
        if (!str_contains($key, ' ') && count($bySurname[$key] ?? []) === 1) {
            return array_key_first($bySurname[$key]);
        }
        return $key;
    };

    // The display name is the spelling used most often, the latest one on a tie.
    $spellings = [];
    foreach ($rows as $row) {
        $name = $applyAlias($row['player']);
        $key = $resolve($name);
        $spellings[$key][$name]['count'] = ($spellings[$key][$name]['count'] ?? 0) + 1;
        $spellings[$key][$name]['last'] = max($spellings[$key][$name]['last'] ?? '', $row['date']);
    }
    $display = [];
    foreach ($spellings as $key => $names) {
        $full = array_filter($names, fn($name) => str_contains($name, ' '), ARRAY_FILTER_USE_KEY) ?: $names;
        uasort($full, fn($a, $b) => [$b['count'], $b['last']] <=> [$a['count'], $a['last']]);
        $display[$key] = array_key_first($full);
    }

    return [$resolve, $display];
}

function bavlnkaAliases(PDO $pdo): array
{
    return $pdo->query("SELECT alias, name FROM bavlnka_aliases ORDER BY alias")->fetchAll(PDO::FETCH_KEY_PAIR);
}

function bavlnkaRanking(PDO $pdo, array $config): array
{
    $cfg = bavlnkaConfig($config);
    bavlnkaEnsureSchema($pdo);

    $tournaments = $pdo->query("SELECT id, date, results_url, source_url, pairs, title FROM bavlnka_tournaments
        WHERE status = 'ok' ORDER BY date DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $rows = $pdo->query("SELECT r.tournament_id, r.rank_from, r.rank_to, r.player, t.date
        FROM bavlnka_results r JOIN bavlnka_tournaments t ON t.id = r.tournament_id
        WHERE t.status = 'ok' ORDER BY t.date, r.rank_from")->fetchAll(PDO::FETCH_ASSOC);

    return bavlnkaComputeRanking($tournaments, $rows, bavlnkaAliases($pdo), $cfg);
}

/**
 * Each pair scores participationPoints plus pointsPerPairBeaten for every pair finishing below it;
 * tied pairs share the points of the places they tie for. Both players get the pair's score.
 * The newest blockSize tournaments count in full, each older block blockDecay less.
 */
function bavlnkaComputeRanking(array $tournaments, array $rows, array $aliases, array $cfg): array
{
    $counted = [];
    foreach (array_slice($tournaments, 0, $cfg['window']) as $i => $t) {
        $weight = round(max(0, 1 - $cfg['blockDecay'] * intdiv($i, $cfg['blockSize'])), 4);
        if ($weight <= 0) break;
        $counted[$t['id']] = [
            'id' => (int) $t['id'],
            'date' => $t['date'],
            'url' => $t['results_url'] ?: $t['source_url'],
            'pairs' => (int) $t['pairs'],
            'weight' => $weight,
        ];
    }

    [$resolve, $display] = bavlnkaNameResolver($rows, $aliases);

    $players = [];
    $seen = [];
    foreach ($rows as $row) {
        $t = $counted[$row['tournament_id']] ?? null;
        if (!$t) continue;
        $key = $resolve($row['player']);
        // A player listed twice in one tournament (a substitute in two pairs) counts once.
        if (isset($seen[$t['id']][$key])) continue;
        $seen[$t['id']][$key] = true;

        $from = (int) $row['rank_from'];
        $to = max($from, (int) $row['rank_to']);
        $beaten = $t['pairs'] - ($from + $to) / 2;
        $points = ($cfg['participationPoints'] + $cfg['pointsPerPairBeaten'] * $beaten) * $t['weight'];

        $players[$key]['name'] = $display[$key];
        $players[$key]['points'] = ($players[$key]['points'] ?? 0) + $points;
        $players[$key]['tournaments'][] = [
            'id' => $t['id'],
            'date' => $t['date'],
            'rank' => $from === $to ? (string) $from : "$from-$to",
            'pairs' => $t['pairs'],
            'weight' => $t['weight'],
            'points' => round($points, 2),
        ];
    }

    $collator = class_exists('Collator') ? new Collator('cs_CZ') : null;
    foreach ($players as &$player) {
        $player['points'] = (int) round($player['points']);
        usort($player['tournaments'], fn($a, $b) => strcmp($b['date'], $a['date']));
    }
    unset($player);
    usort($players, fn($a, $b) => $b['points'] <=> $a['points']
        ?: ($collator ? $collator->compare($a['name'], $b['name']) : strcmp($a['name'], $b['name'])));

    $ranking = [];
    foreach ($players as $i => $player) {
        $previous = $ranking[$i - 1] ?? null;
        $rank = $previous && $previous['points'] === $player['points'] ? $previous['rank'] : $i + 1;
        $ranking[] = ['rank' => $rank] + $player;
    }

    return [
        'updated' => $counted ? reset($counted)['date'] : null,
        'rules' => array_intersect_key($cfg, array_flip(['window', 'blockSize', 'blockDecay', 'participationPoints', 'pointsPerPairBeaten'])),
        'tournaments' => array_values($counted),
        'ranking' => $ranking,
    ];
}

/** Same columns and formatting as the Google Sheet the bkpraha.cz ranking widget used to read. */
function bavlnkaRankingCsv(array $ranking): string
{
    $field = fn($value) => preg_match('/[",\r\n]/', (string) $value) ? '"' . str_replace('"', '""', $value) . '"' : (string) $value;
    $lines = ['Pořadí,Jméno,Body,,Aktualizace'];
    foreach ($ranking['ranking'] as $i => $player) {
        $updated = $i === 0 && $ranking['updated'] ? date('y-m-d', strtotime($ranking['updated'])) : '';
        $lines[] = implode(',', array_map($field, [$player['rank'], $player['name'], number_format($player['points'], 0, ',', ' '), '', $updated]));
    }
    return implode("\r\n", $lines) . "\r\n";
}

function bavlnkaAdminData(PDO $pdo, array $config): array
{
    bavlnkaEnsureSchema($pdo);
    $tournaments = $pdo->query("SELECT t.*, (SELECT COUNT(*) FROM bavlnka_results r WHERE r.tournament_id = t.id) AS players
        FROM bavlnka_tournaments t ORDER BY COALESCE(t.date, t.listed_date) DESC, t.id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $rows = $pdo->query("SELECT r.player, r.cbs_id, t.date FROM bavlnka_results r
        JOIN bavlnka_tournaments t ON t.id = r.tournament_id WHERE t.status = 'ok'")->fetchAll(PDO::FETCH_ASSOC);
    $aliases = bavlnkaAliases($pdo);

    [$resolve, $display] = bavlnkaNameResolver($rows, $aliases);
    $players = [];
    foreach ($rows as $row) {
        $key = $resolve($row['player']);
        $players[$key]['name'] = $display[$key];
        $players[$key]['spellings'][$row['player']] = true;
        if ($row['cbs_id']) $players[$key]['cbsIds'][$row['cbs_id']] = true;
        $players[$key]['count'] = ($players[$key]['count'] ?? 0) + 1;
        $players[$key]['last'] = max($players[$key]['last'] ?? '', $row['date']);
    }
    $players = array_map(fn($p) => [
        'name' => $p['name'],
        'spellings' => array_keys($p['spellings']),
        'cbsIds' => array_keys($p['cbsIds'] ?? []),
        'count' => $p['count'],
        'last' => $p['last'],
    ], array_values($players));
    usort($players, fn($a, $b) => strcmp($a['name'], $b['name']));

    return ['tournaments' => $tournaments, 'aliases' => $aliases, 'players' => $players];
}

function bavlnkaSetAliases(PDO $pdo, array $aliases): void
{
    bavlnkaEnsureSchema($pdo);
    $pdo->beginTransaction();
    $pdo->exec("DELETE FROM bavlnka_aliases");
    $insert = $pdo->prepare("INSERT INTO bavlnka_aliases (alias, name) VALUES (?, ?)");
    foreach ($aliases as $alias => $name) {
        $alias = bavlnkaCleanText((string) $alias);
        $name = bavlnkaCleanText((string) $name);
        if ($alias !== '' && $name !== '' && $alias !== $name) $insert->execute([$alias, $name]);
    }
    $pdo->commit();
}

function bavlnkaAddTournament(PDO $pdo, string $url, ?string $date): void
{
    bavlnkaEnsureSchema($pdo);
    if (!preg_match('#^https?://#i', $url)) {
        throw new InvalidArgumentException('Neplatná adresa výsledků');
    }
    if ($date !== null && $date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new InvalidArgumentException('Datum musí být ve tvaru RRRR-MM-DD');
    }
    if (!bavlnkaInsertTournament($pdo, trim($url), $date ?: null)) {
        throw new InvalidArgumentException('Turnaj s touto adresou už je uložený');
    }
}

function bavlnkaSetTournamentStatus(PDO $pdo, int $id, string $status): void
{
    bavlnkaEnsureSchema($pdo);
    if (!in_array($status, ['pending', 'ignored'], true)) {
        throw new InvalidArgumentException('Stav může být jen pending nebo ignored');
    }
    $pdo->prepare("UPDATE bavlnka_tournaments SET status = ?, attempts = 0 WHERE id = ?")->execute([$status, $id]);
}
