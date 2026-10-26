<?php

require_once __DIR__ . '/bavlnka.php';
require_once __DIR__ . '/prebor.php';

/*
 * The club's results page: per season a tree of sections. A section either holds events added by
 * a results URL, or shows what another part of this app already stores – one of our tournaments
 * (skupinovka), a přebor or the season's Bavlnka tournaments.
 */
const RESULTS_SECTION_KINDS = ['group', 'events', 'tournament', 'prebor', 'bavlnka'];

function resultsEnsureSchema(PDO $pdo): void
{
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $id = 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $suffix = '';
    } else {
        $pdo->exec("SET NAMES utf8mb4");
        $id = 'INT AUTO_INCREMENT PRIMARY KEY';
        $suffix = ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin';
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS results_sections (
        id $id,
        season VARCHAR(16) NOT NULL,
        parent_id INT NULL,
        position INT NOT NULL DEFAULT 0,
        title VARCHAR(255) NOT NULL,
        kind VARCHAR(16) NOT NULL,
        ref VARCHAR(100) NULL,
        note TEXT NULL
    )$suffix");
    $pdo->exec("CREATE TABLE IF NOT EXISTS results_events (
        id $id,
        section_id INT NOT NULL,
        date DATE NULL,
        title VARCHAR(255) NULL,
        url VARCHAR(500) NULL,
        format VARCHAR(16) NULL,
        winner TEXT NULL,
        winner_override VARCHAR(500) NULL,
        ongoing INT NOT NULL DEFAULT 0,
        status VARCHAR(16) NOT NULL DEFAULT 'pending',
        error TEXT NULL,
        fetched_at DATETIME NULL
    )$suffix");
}

/** "2026-27" for anything played from September 2026 to August 2027. */
function resultsSeasonOf(string $date): string
{
    $year = (int) substr($date, 0, 4);
    if ((int) substr($date, 5, 2) < 9) $year--;
    return sprintf('%d-%02d', $year, ($year + 1) % 100);
}

/** @return array{0: string, 1: string} first and last day of a season */
function resultsSeasonRange(string $season): array
{
    if (!preg_match('/^(\d{4})-\d{2}$/', $season, $m)) {
        throw new InvalidArgumentException('Sezóna musí být ve tvaru 2026-27');
    }
    return [$m[1] . '-09-01', ((int) $m[1] + 1) . '-08-31'];
}

/**
 * The first places of a parsed results page: pairs with their players, or teams by name.
 *
 * @return array{kind: string, entries: list<array>}
 */
function resultsWinner(array $pairs): array
{
    $first = array_values(array_filter($pairs, fn($p) => $p['rankFrom'] === 1));
    $isTeam = $first && !array_filter($first, fn($p) => $p['players']);
    return [
        'kind' => $isTeam ? 'team' : 'pair',
        'entries' => array_map(fn($p) => $isTeam
            ? ['name' => $p['title'] ?? '']
            : ['players' => array_map(fn($pl) => ['first' => $pl['first'] ?? '', 'last' => $pl['last'] ?? $pl['name']], $p['players'])],
            $first),
    ];
}

/** "Krása Mikuláš" as Bavlnka stores names, split the way Giborg names are. */
function resultsPairWinner(array $names): array
{
    return ['players' => array_map(fn($name) => bavlnkaSplitName($name), $names)];
}

/** "Mikuláš Krása - Vojtěch Franz" as a přebor result is titled: the last word of each name is the surname. */
function resultsTitledPairWinner(string $title): array
{
    return ['players' => array_map(function ($name) {
        $parts = explode(' ', bavlnkaCleanText($name));
        $last = array_pop($parts);
        return ['first' => implode(' ', $parts), 'last' => $last];
    }, preg_split('/\s+-\s+/u', $title))];
}

function resultsFetchEvent(PDO $pdo, array $event, callable $http): array
{
    $now = date('Y-m-d H:i:s');
    try {
        $parsed = bavlnkaFetchResults($event['url'], $http);
    } catch (RuntimeException $e) {
        // A page we can't read is still worth linking; the winner is then typed in by hand.
        $unreadable = str_starts_with($e->getMessage(), 'Neznámý formát') || str_starts_with($e->getMessage(), 'Ve výsledcích nejsou');
        $status = $unreadable ? 'link' : 'error';
        $pdo->prepare("UPDATE results_events SET status = ?, error = ?, fetched_at = ? WHERE id = ?")
            ->execute([$status, $status === 'error' ? $e->getMessage() : null, $now, $event['id']]);
        return ['id' => (int) $event['id'], 'status' => $status, 'error' => $e->getMessage()];
    }

    $pdo->prepare("UPDATE results_events SET url = ?, format = ?, date = ?, winner = ?, status = 'ok', error = NULL,
        fetched_at = ? WHERE id = ?")
        ->execute([$parsed['resultsUrl'], $parsed['format'], $event['date'] ?: $parsed['date'],
            json_encode(resultsWinner($parsed['pairs']), JSON_UNESCAPED_UNICODE), $now, $event['id']]);
    return ['id' => (int) $event['id'], 'status' => 'ok'];
}

function resultsSection(PDO $pdo, int $id): array
{
    resultsEnsureSchema($pdo);
    $select = $pdo->prepare("SELECT * FROM results_sections WHERE id = ?");
    $select->execute([$id]);
    $section = $select->fetch(PDO::FETCH_ASSOC);
    if (!$section) throw new InvalidArgumentException('Sekce neexistuje');
    return $section;
}

/** Creates a section at the end of its parent, or updates the one with $id. */
function resultsSaveSection(PDO $pdo, array $data, ?int $id = null): int
{
    resultsEnsureSchema($pdo);
    $section = [
        'season' => trim((string) ($data['season'] ?? '')),
        'parent_id' => isset($data['parent_id']) && $data['parent_id'] !== '' && $data['parent_id'] !== null ? (int) $data['parent_id'] : null,
        'title' => bavlnkaCleanText((string) ($data['title'] ?? '')),
        'kind' => (string) ($data['kind'] ?? 'events'),
        'ref' => trim((string) ($data['ref'] ?? '')) ?: null,
        'note' => trim((string) ($data['note'] ?? '')) ?: null,
    ];
    resultsSeasonRange($section['season']);
    if ($section['title'] === '') throw new InvalidArgumentException('Sekce musí mít název');
    if (!in_array($section['kind'], RESULTS_SECTION_KINDS, true)) throw new InvalidArgumentException('Neznámý druh sekce');
    if (in_array($section['kind'], ['tournament', 'prebor'], true) && $section['ref'] === null && $section['note'] === null) {
        throw new InvalidArgumentException('Vyber turnaj nebo přebor, případně napiš poznámku, kdy začne');
    }
    if ($section['parent_id'] !== null) {
        $parent = resultsSection($pdo, $section['parent_id']);
        if ($parent['season'] !== $section['season'] || $parent['parent_id'] !== null || (int) $parent['id'] === $id) {
            throw new InvalidArgumentException('Nadřazená sekce musí být hlavní sekce téže sezóny');
        }
    }

    if ($id === null) {
        $position = $pdo->prepare("SELECT COALESCE(MAX(position), 0) + 1 FROM results_sections WHERE season = ? AND "
            . ($section['parent_id'] === null ? "parent_id IS NULL" : "parent_id = ?"));
        $position->execute(array_values(array_filter([$section['season'], $section['parent_id']], fn($v) => $v !== null)));
        $pdo->prepare("INSERT INTO results_sections (season, parent_id, position, title, kind, ref, note) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$section['season'], $section['parent_id'], (int) $position->fetchColumn(), $section['title'],
                $section['kind'], $section['ref'], $section['note']]);
        return (int) $pdo->lastInsertId();
    }

    resultsSection($pdo, $id);
    $pdo->prepare("UPDATE results_sections SET season = ?, parent_id = ?, title = ?, kind = ?, ref = ?, note = ? WHERE id = ?")
        ->execute([$section['season'], $section['parent_id'], $section['title'], $section['kind'], $section['ref'], $section['note'], $id]);
    return $id;
}

function resultsDeleteSection(PDO $pdo, int $id): void
{
    resultsSection($pdo, $id);
    foreach (["SELECT COUNT(*) FROM results_sections WHERE parent_id = ?", "SELECT COUNT(*) FROM results_events WHERE section_id = ?"] as $sql) {
        $count = $pdo->prepare($sql);
        $count->execute([$id]);
        if ($count->fetchColumn()) throw new InvalidArgumentException('Sekce není prázdná');
    }
    $pdo->prepare("DELETE FROM results_sections WHERE id = ?")->execute([$id]);
}

/** Swaps a section with its neighbour above (-1) or below (+1). */
function resultsMoveSection(PDO $pdo, int $id, int $direction): void
{
    $section = resultsSection($pdo, $id);
    $siblings = $pdo->prepare("SELECT id, position FROM results_sections WHERE season = ? AND "
        . ($section['parent_id'] === null ? "parent_id IS NULL" : "parent_id = " . (int) $section['parent_id'])
        . " ORDER BY position, id");
    $siblings->execute([$section['season']]);
    $ids = array_column($siblings->fetchAll(PDO::FETCH_ASSOC), 'id');
    $at = array_search($id, array_map('intval', $ids), true);
    $other = $ids[$at + ($direction < 0 ? -1 : 1)] ?? null;
    if ($other === null) return;
    [$ids[$at], $ids[$at + ($direction < 0 ? -1 : 1)]] = [$other, $ids[$at]];
    $update = $pdo->prepare("UPDATE results_sections SET position = ? WHERE id = ?");
    foreach ($ids as $position => $sectionId) $update->execute([$position + 1, $sectionId]);
}

function resultsAddEvent(PDO $pdo, int $sectionId, array $data, ?callable $http = null): array
{
    $http ??= 'bavlnkaHttpGet';
    $section = resultsSection($pdo, $sectionId);
    if ($section['kind'] !== 'events') throw new InvalidArgumentException('Do této sekce se turnaje nepřidávají');
    $event = resultsEventFields($data);
    if ($event['url'] === null && $event['winner_override'] === null) {
        throw new InvalidArgumentException('Zadej adresu výsledků nebo vítěze');
    }
    $pdo->prepare("INSERT INTO results_events (section_id, date, title, url, winner_override, ongoing, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([$sectionId, $event['date'], $event['title'], $event['url'], $event['winner_override'], $event['ongoing'],
            $event['url'] ? 'pending' : 'link']);
    $id = (int) $pdo->lastInsertId();
    return $event['url'] ? resultsFetchEvent($pdo, ['id' => $id] + $event, $http) : ['id' => $id, 'status' => 'link'];
}

function resultsEventFields(array $data): array
{
    $date = trim((string) ($data['date'] ?? '')) ?: null;
    if ($date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new InvalidArgumentException('Datum musí být ve tvaru RRRR-MM-DD');
    }
    $url = trim((string) ($data['url'] ?? '')) ?: null;
    if ($url !== null && !preg_match('#^https?://#i', $url)) throw new InvalidArgumentException('Neplatná adresa výsledků');
    return [
        'date' => $date,
        'title' => bavlnkaCleanText((string) ($data['title'] ?? '')) ?: null,
        'url' => $url,
        'winner_override' => bavlnkaCleanText((string) ($data['winner_override'] ?? '')) ?: null,
        // A league or other competition still being played: its first place leads rather than won.
        'ongoing' => empty($data['ongoing']) ? 0 : 1,
    ];
}

function resultsUpdateEvent(PDO $pdo, int $id, array $data): void
{
    resultsEnsureSchema($pdo);
    $event = resultsEventFields($data);
    $sets = ['date = ?', 'title = ?', 'winner_override = ?', 'ongoing = ?'];
    $values = [$event['date'], $event['title'], $event['winner_override'], $event['ongoing']];
    if (isset($data['section_id'])) {
        if (resultsSection($pdo, (int) $data['section_id'])['kind'] !== 'events') {
            throw new InvalidArgumentException('Do této sekce se turnaje nepřidávají');
        }
        $sets[] = 'section_id = ?';
        $values[] = (int) $data['section_id'];
    }
    $values[] = $id;
    $pdo->prepare("UPDATE results_events SET " . implode(', ', $sets) . " WHERE id = ?")->execute($values);
}

function resultsRefetchEvent(PDO $pdo, int $id, ?callable $http = null): array
{
    resultsEnsureSchema($pdo);
    $select = $pdo->prepare("SELECT * FROM results_events WHERE id = ?");
    $select->execute([$id]);
    $event = $select->fetch(PDO::FETCH_ASSOC);
    if (!$event) throw new InvalidArgumentException('Turnaj neexistuje');
    if (!$event['url']) throw new InvalidArgumentException('Turnaj nemá adresu výsledků');
    return resultsFetchEvent($pdo, $event, $http ?? 'bavlnkaHttpGet');
}

function resultsDeleteEvent(PDO $pdo, int $id): void
{
    resultsEnsureSchema($pdo);
    $pdo->prepare("DELETE FROM results_events WHERE id = ?")->execute([$id]);
}

/** @return list<string> seasons with at least one section, newest first */
function resultsSeasons(PDO $pdo): array
{
    resultsEnsureSchema($pdo);
    return $pdo->query("SELECT DISTINCT season FROM results_sections ORDER BY season DESC")->fetchAll(PDO::FETCH_COLUMN);
}

/** Starts a season with the top-level structure of another one: sections, but no events or references. */
function resultsCopySeason(PDO $pdo, string $from, string $to): int
{
    resultsSeasonRange($to);
    if (in_array($to, resultsSeasons($pdo), true)) throw new InvalidArgumentException("Sezóna $to už existuje");
    $select = $pdo->prepare("SELECT * FROM results_sections WHERE season = ? ORDER BY parent_id IS NOT NULL, position, id");
    $select->execute([$from]);
    $insert = $pdo->prepare("INSERT INTO results_sections (season, parent_id, position, title, kind, ref, note) VALUES (?, ?, ?, ?, ?, NULL, NULL)");
    $map = [];
    foreach ($select->fetchAll(PDO::FETCH_ASSOC) as $section) {
        // A skupinovka or přebor of the new season is chosen again; until then the section is a plain list.
        $kind = in_array($section['kind'], ['tournament', 'prebor'], true) ? 'events' : $section['kind'];
        $insert->execute([$to, $section['parent_id'] === null ? null : $map[$section['parent_id']], $section['position'], $section['title'], $kind]);
        $map[$section['id']] = (int) $pdo->lastInsertId();
    }
    return count($map);
}

/**
 * Everything the results page of one season shows, in display order.
 * With $admin the events also carry their status, errors and raw fields for editing.
 */
function resultsPage(PDO $pdo, string $season, bool $admin = false, ?string $today = null): array
{
    resultsEnsureSchema($pdo);
    $today ??= date('Y-m-d');
    [$from, $to] = resultsSeasonRange($season);

    $select = $pdo->prepare("SELECT * FROM results_sections WHERE season = ? ORDER BY position, id");
    $select->execute([$season]);
    $sections = $select->fetchAll(PDO::FETCH_ASSOC);

    $select = $pdo->prepare("SELECT e.* FROM results_events e JOIN results_sections s ON s.id = e.section_id
        WHERE s.season = ? ORDER BY e.date IS NULL, e.date, e.id");
    $select->execute([$season]);
    $events = [];
    foreach ($select->fetchAll(PDO::FETCH_ASSOC) as $event) {
        $item = [
            'id' => (int) $event['id'],
            'date' => $event['date'],
            'title' => $event['title'],
            'url' => $event['url'],
            'ongoing' => (bool) $event['ongoing'],
            'winner' => $event['winner_override'] !== null ? ['kind' => 'text', 'text' => $event['winner_override']]
                : ($event['winner'] ? json_decode($event['winner'], true) : null),
        ];
        if ($admin) {
            $item += array_intersect_key($event, array_flip(['status', 'error', 'format', 'winner_override']));
        }
        $events[(int) $event['section_id']][] = $item;
    }

    $nodes = [];
    foreach ($sections as $section) {
        $node = [
            'id' => (int) $section['id'],
            'parent' => $section['parent_id'] === null ? null : (int) $section['parent_id'],
            'title' => $section['title'],
            'kind' => $section['kind'],
            'ref' => $section['ref'],
            'note' => $section['note'],
            'children' => [],
        ];
        $node += match ($section['kind']) {
            'events' => ['events' => $events[$node['id']] ?? []],
            'tournament' => ['tournament' => resultsTournamentSummary($pdo, $section['ref'])],
            'prebor' => ['prebor' => resultsPreborSummary($pdo, $section['ref'])],
            'bavlnka' => ['events' => resultsBavlnkaEvents($pdo, $from, $to), 'leaders' => resultsBavlnkaLeaders($pdo, $season, $today)],
            default => [],
        };
        $nodes[$node['id']] = $node;
    }

    $tree = [];
    foreach ($nodes as $id => $node) {
        if ($node['parent'] === null) $tree[$id] = $node;
    }
    foreach ($nodes as $node) {
        if ($node['parent'] !== null && isset($tree[$node['parent']])) $tree[$node['parent']]['children'][] = $node;
    }

    return ['season' => $season, 'seasons' => resultsSeasons($pdo), 'sections' => array_values($tree)];
}

/** Our own tournament: the page shows the standings computed in the browser, here only what to load. */
function resultsTournamentSummary(PDO $pdo, ?string $slug): ?array
{
    if ($slug === null) return null;
    $select = $pdo->prepare("SELECT id, name, slug FROM tournaments WHERE slug = ?");
    $select->execute([$slug]);
    $row = $select->fetch(PDO::FETCH_ASSOC);
    return $row ? ['id' => (int) $row['id'], 'name' => $row['name'], 'slug' => $row['slug']] : null;
}

function resultsPreborSummary(PDO $pdo, ?string $slug): ?array
{
    if ($slug === null) return null;
    try {
        $competition = preborCompetition($pdo, $slug);
    } catch (InvalidArgumentException) {
        return null;
    }
    [$rounds, $results] = preborLoad($pdo, $competition['id']);
    usort($rounds, fn($a, $b) => [$a['date'], $a['id']] <=> [$b['date'], $b['id']]);
    $last = end($rounds) ?: null;
    $winners = $last ? array_values(array_filter($results, fn($r) => (int) $r['round_id'] === (int) $last['id'] && (int) $r['rank_from'] === 1)) : [];

    return [
        'slug' => $competition['slug'],
        'title' => $competition['title'],
        'played' => count($rounds),
        'rounds' => $competition['rounds'],
        'lastRound' => $last ? [
            'number' => count($rounds),
            'date' => $last['date'],
            'url' => $last['url'],
            'winner' => ['kind' => 'pair', 'entries' => array_map(fn($r) => $r['title']
                ? resultsTitledPairWinner($r['title']) : resultsPairWinner($r['players']), $winners)],
        ] : null,
    ];
}

/**
 * The top three of the Bavlnka ranking, ties included. The ranking runs across seasons, so it only
 * belongs on the page of the season being played now.
 */
function resultsBavlnkaLeaders(PDO $pdo, string $season, string $today): ?array
{
    if (resultsSeasonOf($today) !== $season) return null;
    $ranking = bavlnkaRanking($pdo, []);
    $top = array_values(array_filter($ranking['ranking'], fn($player) => $player['rank'] <= 3));
    return [
        'updated' => $ranking['updated'],
        'players' => array_map(fn($player) => ['rank' => $player['rank'], 'points' => $player['points']]
            + bavlnkaSplitName($player['name']), $top),
    ];
}

/** The season's Bavlnka tournaments with their winners, oldest first. */
function resultsBavlnkaEvents(PDO $pdo, string $from, string $to): array
{
    bavlnkaEnsureSchema($pdo);
    $select = $pdo->prepare("SELECT t.id, t.date, COALESCE(t.results_url, t.source_url) AS url, r.pair_number, r.rank_from, r.player
        FROM bavlnka_tournaments t LEFT JOIN bavlnka_results r ON r.tournament_id = t.id AND r.rank_from = 1
        WHERE t.status = 'ok' AND t.date BETWEEN ? AND ? ORDER BY t.date, t.id, r.id");
    $select->execute([$from, $to]);

    $events = [];
    foreach ($select->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $id = (int) $row['id'];
        $events[$id] ??= ['id' => $id, 'date' => $row['date'], 'title' => null, 'url' => $row['url'], 'pairs' => []];
        if ($row['player'] !== null) $events[$id]['pairs'][$row['pair_number'] ?? 0][] = $row['player'];
    }
    return array_values(array_map(function ($event) {
        $event['winner'] = ['kind' => 'pair', 'entries' => array_map('resultsPairWinner', array_values($event['pairs']))];
        unset($event['pairs']);
        return $event;
    }, $events));
}
