<?php

require_once __DIR__ . '/bavlnka.php';

// The competitions running when the rankings moved here from Google Sheets; later seasons are added in the admin.
const PREBOR_SEED_COMPETITIONS = [
    [
        'slug' => 'top-2026',
        'season' => '2026/27',
        'title' => 'Topový přebor BKP 2026/27',
        'unit' => '%',
        'best' => 8,
        'rounds' => 10,
        'registration_csv' => 'https://docs.google.com/spreadsheets/d/19mDwBYxfeAQAPIqr90RShBPGc1Vb35OEo7NdgFPZGaQ/export?format=csv',
    ],
    [
        'slug' => 'imp-2026',
        'season' => '2026/27',
        'title' => 'Impový přebor BKP 2026/27',
        'unit' => 'VP',
        'best' => 8,
        'rounds' => 10,
        'registration_csv' => 'https://docs.google.com/spreadsheets/d/1z-Py4Kub_hNw-mXkyFplPMnw2Ox0NHENjBNxFHUrYzY/export?format=csv',
    ],
];

const PREBOR_TIME_BUDGET = 60;
const PREBOR_RESERVED_SLUGS = ['admin', 'update', 'competitions', 'rounds'];
const PREBOR_COMPETITION_FIELDS = ['slug', 'season', 'title', 'unit', 'best', 'rounds', 'registration_csv'];

function preborEnsureSchema(PDO $pdo): void
{
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $id = 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $suffix = '';
    } else {
        $pdo->exec("SET NAMES utf8mb4");
        $id = 'INT AUTO_INCREMENT PRIMARY KEY';
        $suffix = ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin';
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS prebor_competitions (
        id $id,
        slug VARCHAR(64) NOT NULL UNIQUE,
        season VARCHAR(16) NOT NULL,
        title VARCHAR(255) NOT NULL,
        unit VARCHAR(8) NOT NULL,
        best INT NOT NULL,
        rounds INT NOT NULL,
        registration_csv VARCHAR(500) NULL
    )$suffix");
    $pdo->exec("CREATE TABLE IF NOT EXISTS prebor_rounds (
        id $id,
        competition_id INT NOT NULL,
        date DATE NULL,
        url VARCHAR(500) NULL,
        source VARCHAR(8) NOT NULL,
        title VARCHAR(255) NULL,
        pairs INT NULL,
        status VARCHAR(16) NOT NULL DEFAULT 'pending',
        error TEXT NULL,
        fetched_at DATETIME NULL
    )$suffix");
    $pdo->exec("CREATE TABLE IF NOT EXISTS prebor_results (
        id $id,
        round_id INT NOT NULL,
        pair_number INT NULL,
        rank_from INT NOT NULL,
        rank_to INT NOT NULL,
        score DECIMAL(8,2) NULL,
        title VARCHAR(255) NULL,
        players TEXT NOT NULL
    )$suffix");
    $pdo->exec("CREATE TABLE IF NOT EXISTS prebor_entries (
        competition_id INT NOT NULL,
        number INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        players TEXT NOT NULL,
        PRIMARY KEY (competition_id, number)
    )$suffix");
    $pdo->exec("CREATE TABLE IF NOT EXISTS prebor_assignments (
        round_id INT NOT NULL,
        players_key VARCHAR(255) NOT NULL,
        entry_number INT NOT NULL,
        PRIMARY KEY (round_id, players_key)
    )$suffix");

    if ((int) $pdo->query("SELECT COUNT(*) FROM prebor_competitions")->fetchColumn() === 0) {
        foreach (PREBOR_SEED_COMPETITIONS as $competition) {
            preborInsertCompetition($pdo, $competition);
        }
    }
}

function preborInsertCompetition(PDO $pdo, array $competition): int
{
    $pdo->prepare("INSERT INTO prebor_competitions (" . implode(', ', PREBOR_COMPETITION_FIELDS) . ") VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute(array_map(fn($field) => $competition[$field], PREBOR_COMPETITION_FIELDS));
    return (int) $pdo->lastInsertId();
}

function preborCompetitionRow(array $row): array
{
    return ['id' => (int) $row['id'], 'best' => (int) $row['best'], 'rounds' => (int) $row['rounds']] + $row;
}

/** @return list<array> newest season first */
function preborCompetitions(PDO $pdo): array
{
    preborEnsureSchema($pdo);
    $rows = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM prebor_rounds r WHERE r.competition_id = c.id AND r.status = 'ok') AS played
        FROM prebor_competitions c ORDER BY c.season DESC, c.title")->fetchAll(PDO::FETCH_ASSOC);
    return array_map(fn($row) => ['played' => (int) $row['played']] + preborCompetitionRow($row), $rows);
}

function preborCompetition(PDO $pdo, string $slug): array
{
    preborEnsureSchema($pdo);
    $select = $pdo->prepare("SELECT * FROM prebor_competitions WHERE slug = ?");
    $select->execute([$slug]);
    $row = $select->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new InvalidArgumentException("Přebor $slug neexistuje");
    }
    return preborCompetitionRow($row);
}

/** Creates a competition, or updates the one with $id. */
function preborSaveCompetition(PDO $pdo, array $data, ?int $id = null): array
{
    preborEnsureSchema($pdo);
    $competition = [
        'slug' => trim((string) ($data['slug'] ?? '')),
        'season' => trim((string) ($data['season'] ?? '')),
        'title' => bavlnkaCleanText((string) ($data['title'] ?? '')),
        'unit' => trim((string) ($data['unit'] ?? '')),
        'best' => (int) ($data['best'] ?? 0),
        'rounds' => (int) ($data['rounds'] ?? 0),
        'registration_csv' => trim((string) ($data['registration_csv'] ?? '')) ?: null,
    ];
    if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $competition['slug']) || in_array($competition['slug'], PREBOR_RESERVED_SLUGS, true)) {
        throw new InvalidArgumentException('Adresa smí obsahovat jen malá písmena bez diakritiky, číslice a pomlčky');
    }
    if ($competition['season'] === '' || $competition['title'] === '' || $competition['unit'] === '') {
        throw new InvalidArgumentException('Vyplň sezónu, název a jednotku výsledků');
    }
    if ($competition['rounds'] < 1 || $competition['best'] < 1 || $competition['best'] > $competition['rounds']) {
        throw new InvalidArgumentException('Počet započítaných kol musí být mezi 1 a počtem kol');
    }
    if ($competition['registration_csv'] !== null && !preg_match('#^https?://#i', $competition['registration_csv'])) {
        throw new InvalidArgumentException('Neplatná adresa tabulky přihlášek');
    }

    $taken = $pdo->prepare("SELECT id FROM prebor_competitions WHERE slug = ?");
    $taken->execute([$competition['slug']]);
    $other = $taken->fetchColumn();
    if ($other !== false && (int) $other !== $id) {
        throw new InvalidArgumentException('Přebor s touto adresou už existuje');
    }

    if ($id === null) {
        $id = preborInsertCompetition($pdo, $competition);
    } else {
        $sets = implode(', ', array_map(fn($field) => "$field = ?", PREBOR_COMPETITION_FIELDS));
        $pdo->prepare("UPDATE prebor_competitions SET $sets WHERE id = ?")
            ->execute([...array_map(fn($field) => $competition[$field], PREBOR_COMPETITION_FIELDS), $id]);
    }
    return ['id' => $id] + $competition;
}

/** One player regardless of name order, case, diacritics or a "ml."/"st." suffix. */
function preborPersonKey(string $name): string
{
    $tokens = array_filter(explode(' ', bavlnkaFold($name)),
        fn($t) => preg_match('/\p{L}/u', $t) && !in_array(rtrim($t, '.'), ['ml', 'st', 'jr', 'sr'], true));
    sort($tokens);
    return implode(' ', $tokens);
}

/** @param list<string> $names */
function preborPlayersKey(array $names): string
{
    $keys = array_map('preborPersonKey', $names);
    sort($keys);
    return implode('|', $keys);
}

/**
 * Reads the registration sheet: a numbered row per dvojice with players 1–2 as surname, first name, suffix,
 * and players 3–4 on the next, unnumbered row.
 *
 * @return list<array{number: int, title: string, players: list<string>}>
 */
function preborParseRoster(string $csv): array
{
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, preg_replace('/^\xEF\xBB\xBF/', '', $csv));
    rewind($stream);

    $player = fn(array $row, int $at) => bavlnkaCleanText(implode(' ', [$row[$at + 1] ?? '', $row[$at] ?? '', $row[$at + 2] ?? '']));
    $entries = [];
    while (($row = fgetcsv($stream, null, ',', '"', '')) !== false) {
        $number = trim($row[0] ?? '');
        // Empty rows of the sheet hold formula errors like "#VALUE!" instead of names.
        $players = array_values(array_filter([$player($row, 2), $player($row, 5)],
            fn($p) => preg_match('/\p{L}/u', $p) && !str_starts_with($p, '#')));
        if (!$players || trim($row[2] ?? '') === '') continue;
        if (ctype_digit($number)) {
            $entries[] = ['number' => (int) $number, 'title' => '', 'players' => $players];
        } elseif ($number === '' && $entries) {
            array_push($entries[count($entries) - 1]['players'], ...$players);
        }
    }
    fclose($stream);

    foreach ($entries as &$entry) {
        $entry['title'] = implode(' - ', $entry['players']);
    }
    return $entries;
}

function preborImportRoster(PDO $pdo, string $slug, ?callable $http = null): int
{
    $http ??= 'bavlnkaHttpGet';
    $competition = preborCompetition($pdo, $slug);
    if (!$competition['registration_csv']) {
        throw new InvalidArgumentException('Přebor nemá zadanou tabulku přihlášek');
    }
    [$body, , $status] = $http($competition['registration_csv']);
    if ($status >= 400) {
        throw new RuntimeException("Tabulka přihlášek vrátila HTTP $status");
    }
    $entries = preborParseRoster($body);
    if (!$entries) {
        throw new RuntimeException('V tabulce přihlášek nejsou žádné dvojice');
    }

    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM prebor_entries WHERE competition_id = ?")->execute([$competition['id']]);
    $insert = $pdo->prepare("INSERT INTO prebor_entries (competition_id, number, title, players) VALUES (?, ?, ?, ?)");
    foreach ($entries as $entry) {
        $insert->execute([$competition['id'], $entry['number'], $entry['title'], json_encode($entry['players'], JSON_UNESCAPED_UNICODE)]);
    }
    $pdo->commit();
    return count($entries);
}

function preborParseNumber(string $value): ?float
{
    $value = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], trim($value));
    return preg_match('/^-?\d+(\.\d+)?$/', $value) ? (float) $value : null;
}

/**
 * Results typed or pasted from a spreadsheet, one pair per line: optionally the place and pair number,
 * the players separated by " - ", and the score as the last number on the line.
 */
function preborParseManual(string $text): array
{
    $pairs = [];
    foreach (preg_split('/\R/', $text) as $line) {
        if (trim($line) === '') continue;
        $fields = str_contains($line, "\t") ? explode("\t", $line)
            : (str_contains($line, ';') ? explode(';', $line) : str_getcsv($line, ',', '"', ''));
        $fields = array_map('trim', $fields);

        $nameAt = null;
        foreach ($fields as $i => $field) {
            if (preg_match('/\p{L}.*\s-\s.*\p{L}/u', $field)) {
                $nameAt = $i;
                break;
            }
        }
        $score = null;
        for ($i = count($fields) - 1; $i > ($nameAt ?? PHP_INT_MAX); $i--) {
            if (($score = preborParseNumber($fields[$i])) !== null) break;
        }
        if ($nameAt === null || $score === null) continue;

        $names = array_values(array_filter(array_map('bavlnkaPlayerName', preg_split('/\s+[-–]\s+/u', $fields[$nameAt]))));
        $before = $fields[$nameAt - 1] ?? '';
        $pairs[] = [
            'pair' => $nameAt > 0 && ctype_digit($before) ? (int) $before : null,
            'title' => implode(' - ', $names),
            'score' => round($score, 2),
            'players' => array_map(fn($name) => ['name' => $name, 'cbsId' => null], $names),
        ];
    }

    usort($pairs, fn($a, $b) => $b['score'] <=> $a['score']);
    foreach ($pairs as $i => &$pair) {
        $tiedBefore = count(array_filter($pairs, fn($p) => $p['score'] > $pair['score']));
        $tied = count(array_filter($pairs, fn($p) => $p['score'] == $pair['score']));
        $pair['rankFrom'] = $tiedBefore + 1;
        $pair['rankTo'] = $tiedBefore + $tied;
    }
    return $pairs;
}

function preborStoreResults(PDO $pdo, int $roundId, array $pairs): void
{
    $pdo->prepare("DELETE FROM prebor_results WHERE round_id = ?")->execute([$roundId]);
    $insert = $pdo->prepare("INSERT INTO prebor_results (round_id, pair_number, rank_from, rank_to, score, title, players)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($pairs as $pair) {
        $insert->execute([$roundId, $pair['pair'], $pair['rankFrom'], $pair['rankTo'], $pair['score'],
            $pair['title'] ?? null, json_encode(array_column($pair['players'], 'name'), JSON_UNESCAPED_UNICODE)]);
    }
}

/**
 * A presentation with robot averages lists the robots as pairs. They are not players: they are left
 * out and the people are ranked among themselves, ties sharing the places.
 */
function preborWithoutRobots(array $pairs): array
{
    $people = array_values(array_filter($pairs, fn($pair) =>
        array_filter($pair['players'], fn($player) => !preg_match('/^\s*robot\s*$/iu', $player['name']))));
    if (count($people) === count($pairs)) return $pairs;

    usort($people, fn($a, $b) => $b['score'] <=> $a['score']);
    foreach ($people as $i => $pair) {
        $from = $i;
        while ($from > 0 && $people[$from - 1]['score'] == $pair['score']) $from--;
        $to = $i;
        while ($to < count($people) - 1 && $people[$to + 1]['score'] == $pair['score']) $to++;
        $people[$i]['rankFrom'] = $from + 1;
        $people[$i]['rankTo'] = $to + 1;
    }
    return $people;
}

function preborFetchRound(PDO $pdo, array $round, callable $http): array
{
    try {
        $parsed = bavlnkaFetchResults($round['url'], $http);
        $parsed['pairs'] = preborWithoutRobots($parsed['pairs']);
        $date = $round['date'] ?: $parsed['date'];
        if (!$date) {
            throw new RuntimeException('Neznámé datum kola, zadej ho ručně');
        }
    } catch (Throwable $e) {
        $pdo->prepare("UPDATE prebor_rounds SET status = 'error', error = ?, fetched_at = ? WHERE id = ?")
            ->execute([$e->getMessage(), date('Y-m-d H:i:s'), $round['id']]);
        throw $e;
    }

    $pdo->beginTransaction();
    try {
        preborStoreResults($pdo, (int) $round['id'], $parsed['pairs']);
        $pdo->prepare("UPDATE prebor_rounds SET date = ?, title = ?, pairs = ?, status = 'ok', error = NULL, fetched_at = ?
            WHERE id = ?")->execute([$date, $parsed['title'], count($parsed['pairs']), date('Y-m-d H:i:s'), $round['id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return ['id' => (int) $round['id'], 'date' => $date, 'pairs' => count($parsed['pairs'])];
}

function preborValidDate(?string $date): ?string
{
    if ($date === null || $date === '') return null;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new InvalidArgumentException('Datum musí být ve tvaru RRRR-MM-DD');
    }
    return $date;
}

/** Adds a round either from a results URL, downloaded right away, or from pasted results. */
function preborAddRound(PDO $pdo, string $slug, array $data, ?callable $http = null): array
{
    $http ??= 'bavlnkaHttpGet';
    $competitionId = preborCompetition($pdo, $slug)['id'];
    $date = preborValidDate($data['date'] ?? null);
    $url = trim((string) ($data['url'] ?? ''));
    $manual = trim((string) ($data['results'] ?? ''));

    if ($manual !== '') {
        if (!$date) throw new InvalidArgumentException('U ručně zadaných výsledků je potřeba datum');
        $pairs = preborParseManual($manual);
        if (!$pairs) throw new InvalidArgumentException('Ve vložených výsledcích není žádná dvojice se skóre');
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO prebor_rounds (competition_id, date, url, source, pairs, status, fetched_at)
            VALUES (?, ?, ?, 'manual', ?, 'ok', ?)")
            ->execute([$competitionId, $date, $url !== '' ? $url : null, count($pairs), date('Y-m-d H:i:s')]);
        $id = (int) $pdo->lastInsertId();
        preborStoreResults($pdo, $id, $pairs);
        $pdo->commit();
        return ['id' => $id, 'date' => $date, 'pairs' => count($pairs)];
    }

    if (!preg_match('#^https?://#i', $url)) {
        throw new InvalidArgumentException('Zadej adresu výsledků nebo vlož výsledky');
    }
    $exists = $pdo->prepare("SELECT 1 FROM prebor_rounds WHERE competition_id = ? AND url = ?");
    $exists->execute([$competitionId, $url]);
    if ($exists->fetchColumn()) {
        throw new InvalidArgumentException('Kolo s touto adresou už je uložené');
    }
    $pdo->prepare("INSERT INTO prebor_rounds (competition_id, date, url, source, status) VALUES (?, ?, ?, 'tc', 'pending')")
        ->execute([$competitionId, $date, $url]);
    $round = ['id' => (int) $pdo->lastInsertId(), 'url' => $url, 'date' => $date];
    try {
        return preborFetchRound($pdo, $round, $http);
    } catch (Throwable $e) {
        throw new InvalidArgumentException('Kolo je uložené, ale výsledky se nepodařilo stáhnout: ' . $e->getMessage());
    }
}

/** Downloads all rounds again, so corrections made in Tournament Calculator after the evening get in. */
function preborUpdate(PDO $pdo, ?callable $http = null): array
{
    $http ??= 'bavlnkaHttpGet';
    preborEnsureSchema($pdo);
    $start = microtime(true);
    $report = ['processed' => [], 'errors' => []];
    $rounds = $pdo->query("SELECT * FROM prebor_rounds WHERE source = 'tc' AND status <> 'ignored'
        ORDER BY date DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rounds as $round) {
        if (microtime(true) - $start > PREBOR_TIME_BUDGET) break;
        try {
            $report['processed'][] = preborFetchRound($pdo, $round, $http) + ['competition' => (int) $round['competition_id']];
        } catch (Throwable $e) {
            $report['errors'][] = ['id' => (int) $round['id'], 'url' => $round['url'], 'error' => $e->getMessage()];
        }
    }
    return $report;
}

function preborRoundAction(PDO $pdo, int $id, string $action, ?callable $http = null): void
{
    $http ??= 'bavlnkaHttpGet';
    preborEnsureSchema($pdo);
    $select = $pdo->prepare("SELECT * FROM prebor_rounds WHERE id = ?");
    $select->execute([$id]);
    $round = $select->fetch(PDO::FETCH_ASSOC);
    if (!$round) throw new InvalidArgumentException('Kolo neexistuje');

    switch ($action) {
        case 'refetch':
            if ($round['source'] !== 'tc') throw new InvalidArgumentException('Ručně zadané kolo nejde stáhnout');
            try {
                preborFetchRound($pdo, $round, $http);
            } catch (Throwable $e) {
                throw new InvalidArgumentException($e->getMessage());
            }
            break;
        case 'ignore':
            $pdo->prepare("UPDATE prebor_rounds SET status = 'ignored' WHERE id = ?")->execute([$id]);
            break;
        case 'restore':
            $pdo->prepare("UPDATE prebor_rounds SET status = 'ok' WHERE id = ?")->execute([$id]);
            break;
        case 'delete':
            $pdo->prepare("DELETE FROM prebor_results WHERE round_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM prebor_assignments WHERE round_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM prebor_rounds WHERE id = ?")->execute([$id]);
            break;
        default:
            throw new InvalidArgumentException("Neznámá akce $action");
    }
}

/** $entryNumber: a registered dvojice, 0 for a pair of its own, null to drop the decision. */
function preborSetAssignment(PDO $pdo, int $roundId, string $playersKey, ?int $entryNumber): void
{
    preborEnsureSchema($pdo);
    $pdo->prepare("DELETE FROM prebor_assignments WHERE round_id = ? AND players_key = ?")->execute([$roundId, $playersKey]);
    if ($entryNumber !== null) {
        $pdo->prepare("INSERT INTO prebor_assignments (round_id, players_key, entry_number) VALUES (?, ?, ?)")
            ->execute([$roundId, $playersKey, $entryNumber]);
    }
}

/**
 * Decides which dvojice a pair's result belongs to: a decision made in the admin, then the registered
 * dvojice holding both players, then the only one holding one of them (a substitute played),
 * otherwise a pair of its own, merged across rounds by its players.
 *
 * @return array{0: string, 1: string} entry key, how it was decided
 */
function preborResolve(array $players, int $roundId, array $entries, array $assignments): array
{
    $playersKey = preborPlayersKey($players);
    $assigned = $assignments[$roundId][$playersKey] ?? null;
    if ($assigned !== null) {
        return $assigned > 0 && isset($entries[$assigned]) ? ["entry:$assigned", 'assigned'] : ["pair:$playersKey", 'own'];
    }

    $keys = array_map('preborPersonKey', $players);
    $hits = [];
    foreach ($entries as $number => $entry) {
        $found = count(array_intersect($keys, $entry['keys']));
        if ($found) $hits[$number] = $found;
    }
    $full = array_keys(array_filter($hits, fn($found) => $found === count($keys)));
    if (count($full) === 1) return ['entry:' . $full[0], 'registered'];
    if (count($hits) === 1) return ['entry:' . array_key_first($hits), 'substitute'];
    return ["pair:$playersKey", $hits ? 'conflict' : 'unregistered'];
}

/**
 * @param array $competition row of prebor_competitions
 * @param list<array> $rounds rows of prebor_rounds with status ok
 * @param list<array> $results rows of prebor_results for those rounds, players decoded
 * @param array<int, array{title: string, players: list<string>}> $entries registered dvojice by number
 * @param array<int, array<string, int>> $assignments round id => players key => entry number
 */
function preborComputeRanking(array $competition, array $rounds, array $results, array $entries, array $assignments): array
{
    usort($rounds, fn($a, $b) => [$a['date'], $a['id']] <=> [$b['date'], $b['id']]);
    $roundInfo = [];
    foreach ($rounds as $i => $round) {
        $roundInfo[$round['id']] = [
            'id' => (int) $round['id'],
            'number' => $i + 1,
            'date' => $round['date'],
            'url' => $round['url'],
            'pairs' => (int) $round['pairs'],
        ];
    }
    foreach ($entries as &$entry) {
        $entry['keys'] = array_map('preborPersonKey', $entry['players']);
    }
    unset($entry);

    $standings = [];
    foreach ($results as $result) {
        $round = $roundInfo[$result['round_id']] ?? null;
        if (!$round || $result['score'] === null) continue;
        [$key, $kind] = preborResolve($result['players'], $round['id'], $entries, $assignments);
        $number = str_starts_with($key, 'entry:') ? (int) substr($key, 6) : null;

        $standings[$key] ??= [
            'number' => $number,
            'name' => $number ? $entries[$number]['title'] : ($result['title'] ?: implode(' - ', $result['players'])),
            'players' => $number ? $entries[$number]['players'] : $result['players'],
            'results' => [],
        ];
        $from = (int) $result['rank_from'];
        $to = max($from, (int) $result['rank_to']);
        $score = (float) $result['score'];
        // Two lineups of one dvojice in the same round: the better result stands.
        if (isset($standings[$key]['results'][$round['id']]) && $standings[$key]['results'][$round['id']]['score'] >= $score) continue;
        $standings[$key]['results'][$round['id']] = [
            'round' => $round['id'],
            'number' => $round['number'],
            'score' => $score,
            'rank' => $from === $to ? (string) $from : "$from-$to",
            'lineup' => $result['title'] ?: implode(' - ', $result['players']),
            'kind' => $kind,
            'counted' => false,
        ];
    }

    foreach ($standings as &$standing) {
        $byScore = $standing['results'];
        uasort($byScore, fn($a, $b) => $b['score'] <=> $a['score'] ?: $a['number'] <=> $b['number']);
        $total = 0.0;
        foreach (array_slice(array_keys($byScore), 0, $competition['best']) as $roundId) {
            $standing['results'][$roundId]['counted'] = true;
            $total += $standing['results'][$roundId]['score'];
        }
        $standing['total'] = round($total, 2);
        $standing['played'] = count($standing['results']);
        usort($standing['results'], fn($a, $b) => $a['number'] <=> $b['number']);
    }
    unset($standing);

    $collator = class_exists('Collator') ? new Collator('cs_CZ') : null;
    $standings = array_values($standings);
    usort($standings, fn($a, $b) => $b['total'] <=> $a['total']
        ?: ($collator ? $collator->compare($a['name'], $b['name']) : strcmp($a['name'], $b['name'])));

    $ranking = [];
    foreach ($standings as $i => $standing) {
        $previous = $ranking[$i - 1] ?? null;
        $rank = $previous && $previous['total'] === $standing['total'] ? $previous['rank'] : $i + 1;
        $ranking[] = ['rank' => $rank] + $standing;
    }

    return [
        'slug' => $competition['slug'],
        'season' => $competition['season'],
        'title' => $competition['title'],
        'unit' => $competition['unit'],
        'best' => $competition['best'],
        'totalRounds' => $competition['rounds'],
        'updated' => $rounds ? end($rounds)['date'] : null,
        'rounds' => array_values($roundInfo),
        'ranking' => $ranking,
    ];
}

/** @return array{0: list<array>, 1: list<array>, 2: array, 3: array} rounds, results, entries, assignments */
function preborLoad(PDO $pdo, int $competitionId): array
{
    $select = $pdo->prepare("SELECT * FROM prebor_rounds WHERE competition_id = ? AND status = 'ok'");
    $select->execute([$competitionId]);
    $rounds = $select->fetchAll(PDO::FETCH_ASSOC);

    $select = $pdo->prepare("SELECT r.* FROM prebor_results r JOIN prebor_rounds t ON t.id = r.round_id
        WHERE t.competition_id = ? AND t.status = 'ok' ORDER BY r.round_id, r.rank_from, r.id");
    $select->execute([$competitionId]);
    $results = array_map(fn($r) => ['players' => json_decode($r['players'], true)] + $r, $select->fetchAll(PDO::FETCH_ASSOC));

    $select = $pdo->prepare("SELECT number, title, players FROM prebor_entries WHERE competition_id = ? ORDER BY number");
    $select->execute([$competitionId]);
    $entries = [];
    foreach ($select->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $entries[(int) $row['number']] = ['title' => $row['title'], 'players' => json_decode($row['players'], true)];
    }

    $select = $pdo->prepare("SELECT a.round_id, a.players_key, a.entry_number FROM prebor_assignments a
        JOIN prebor_rounds t ON t.id = a.round_id WHERE t.competition_id = ?");
    $select->execute([$competitionId]);
    $assignments = [];
    foreach ($select->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $assignments[(int) $row['round_id']][$row['players_key']] = (int) $row['entry_number'];
    }

    return [$rounds, $results, $entries, $assignments];
}

function preborRanking(PDO $pdo, string $slug): array
{
    $competition = preborCompetition($pdo, $slug);
    return preborComputeRanking($competition, ...preborLoad($pdo, $competition['id']));
}

/** Same columns and formatting as the Google Sheet the bkpraha.cz widget used to read. */
function preborRankingCsv(array $ranking): string
{
    $field = fn($value) => preg_match('/[",\r\n]/', (string) $value) ? '"' . str_replace('"', '""', $value) . '"' : (string) $value;
    $lines = ['Pořadí,Dvojice,Body,Kola,Aktualizace'];
    foreach ($ranking['ranking'] as $i => $standing) {
        $first = $i === 0;
        $lines[] = implode(',', array_map($field, [
            $standing['rank'],
            $standing['name'],
            number_format($standing['total'], 2, ',', ''),
            $first ? count($ranking['rounds']) : '',
            $first && $ranking['updated'] ? date('y-m-d', strtotime($ranking['updated'])) : '',
        ]));
    }
    return implode("\r\n", $lines) . "\r\n";
}

function preborAdminData(PDO $pdo): array
{
    $data = [];
    foreach (preborCompetitions($pdo) as $competition) {
        [, $results, $entries, $assignments] = preborLoad($pdo, $competition['id']);

        $select = $pdo->prepare("SELECT * FROM prebor_rounds WHERE competition_id = ? ORDER BY date, id");
        $select->execute([$competition['id']]);
        $rounds = $select->fetchAll(PDO::FETCH_ASSOC);
        $dates = array_column($rounds, 'date', 'id');

        $keyed = $entries;
        foreach ($keyed as &$entry) {
            $entry['keys'] = array_map('preborPersonKey', $entry['players']);
        }
        unset($entry);

        // Every result not matched to a dvojice holding both players, so a person can check or override it.
        $review = [];
        foreach ($results as $result) {
            [$key, $kind] = preborResolve($result['players'], (int) $result['round_id'], $keyed, $assignments);
            if ($kind === 'registered') continue;
            $review[] = [
                'round' => (int) $result['round_id'],
                'date' => $dates[$result['round_id']] ?? null,
                'lineup' => $result['title'] ?: implode(' - ', $result['players']),
                'players' => $result['players'],
                'playersKey' => preborPlayersKey($result['players']),
                'kind' => $kind,
                'entry' => str_starts_with($key, 'entry:') ? (int) substr($key, 6) : 0,
            ];
        }

        $data[] = $competition + [
            'roundList' => $rounds,
            'entries' => array_map(fn($number, $e) => ['number' => $number] + $e, array_keys($entries), $entries),
            'review' => $review,
        ];
    }
    return $data;
}
