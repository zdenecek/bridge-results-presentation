<?php

require_once __DIR__ . '/results.php';

/*
 * One-off import of the hand-written results pages on bkpraha.cz (/vysledky/ and /archiv-vysledku-…/).
 * A page is a list of blocks (h1), competitions (h2) and their parts (h3); each tournament is a sentence
 * with the date ("Vítězem turnaje hraného 15. 9. 2026 je pár"), one 🏆 line per winner and a link.
 * Winners are taken from the page itself, so the import does not have to open every results page.
 */

const RESULTS_IMPORT_SKIP = ['/^VÝSLEDKY/u', '/ŽEBŘÍČEK/u', '/CELKOVÉ POŘADÍ/u', '/ARCHIV/u'];
const RESULTS_IMPORT_KEEP_CASE = ['BKP', 'ČBS', 'PBL'];
const RESULTS_IMPORT_MONTHS = ['leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec'];
// Names in headings that the sentences on the pages never spell out.
const RESULTS_IMPORT_PROPER = ['panonia' => 'Panonia', 'praha' => 'Praha', 'prahy' => 'Prahy', 'bavlnka' => 'Bavlnka',
    'aleny' => 'Aleny', 'maria' => 'Maria', 'petrovských' => 'Petrovských', 'tomáše' => 'Tomáše', 'etlíka' => 'Etlíka',
    'blanky' => 'Blanky', 'medlínové' => 'Medlínové', 'ústí' => 'Ústí', 'labem' => 'Labem', 'znojma' => 'Znojma',
    'pardubic' => 'Pardubic', 'českých' => 'Českých', 'budějovic' => 'Budějovic', 'havířova' => 'Havířova',
    'metuje' => 'Metuje', 'sněžka' => 'Sněžka'];

/** All-capitals with at least two letters, as bkpraha.cz writes surnames and headings. */
function resultsImportIsCaps(string $word): bool
{
    $letters = preg_replace('/[^\p{L}]/u', '', $word);
    return mb_strlen($letters) > 1 && mb_strtoupper($letters) === $letters;
}

/**
 * "VELKÁ CENA ÚSTÍ NAD LABEM" → "Velká cena Ústí nad Labem": a word is capitalised the way the page's
 * sentences write it, otherwise lower case; single letters (Skupinovka A) and acronyms stay.
 */
function resultsImportHeading(string $text, array $known): string
{
    $words = explode(' ', bavlnkaCleanText($text));
    $lastIndex = count($words) - 1;
    foreach ($words as $i => &$word) {
        // A single letter stays a capital only at the end, as in "Skupinovka A"; elsewhere it is "a".
        if (mb_strlen($word) === 1 && $i > 0) {
            if ($i !== $lastIndex) $word = mb_strtolower($word);
            continue;
        }
        if (!resultsImportIsCaps($word) || in_array($word, RESULTS_IMPORT_KEEP_CASE, true)) continue;
        $lower = mb_strtolower($word);
        $word = $known[$lower] ?? RESULTS_IMPORT_PROPER[$lower] ?? ($i === 0 ? mb_strtoupper(mb_substr($lower, 0, 1)) . mb_substr($lower, 1) : $lower);
    }
    unset($word);
    $heading = implode(' ', $words);
    return mb_strtoupper(mb_substr($heading, 0, 1)) . mb_substr($heading, 1);
}

/**
 * A date from the page: a missing year is the one of the tournament before it, and a year outside the season
 * is a typo (a 2025/26 page once said 14. 4. 2025) corrected to the season's year for that month.
 */
function resultsImportDate(int $day, int $month, ?int $year, ?int $previousYear, ?string $season): ?string
{
    $seasonYear = $season ? (int) substr($season, 0, 4) + ($month < 9 ? 1 : 0) : null;
    $written = $year !== null;
    $year ??= $previousYear ?? $seasonYear;
    if ($year === null) return null;
    $date = bavlnkaDate((string) $day, (string) $month, (string) $year);
    if ($date && $season && $written) {
        [$from, $to] = resultsSeasonRange($season);
        if (($date < $from || $date > $to) && abs($year - $seasonYear) === 1) $date = bavlnkaDate((string) $day, (string) $month, (string) $seasonYear);
    }
    return $date;
}

/** "Mikuláš KRÁSA - Vojtěch FRANZ": the capitals are the surname. */
function resultsImportPlayers(string $line): ?array
{
    $players = [];
    foreach (preg_split('/\s+[-–]\s+/u', $line) as $name) {
        $first = [];
        $last = [];
        foreach (explode(' ', bavlnkaCleanText($name)) as $word) {
            if (resultsImportIsCaps($word)) {
                $last[] = implode('-', array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)) . mb_strtolower(mb_substr($w, 1)), explode('-', $word)));
            } else {
                $first[] = $word;
            }
        }
        if (!$last) return null;
        $players[] = ['first' => implode(' ', $first), 'last' => implode(' ', $last)];
    }
    return $players;
}

/**
 * Reads one results page into sections and events, without touching the database.
 *
 * @return array{season: ?string, sections: list<array>}
 */
function resultsParseListing(string $html, string $pageUrl, ?string $season = null): array
{
    $xp = bavlnkaLoadHtml($html);
    $nodes = [];
    foreach ($xp->query('//h1 | //h2 | //h3 | //p') as $node) {
        $text = bavlnkaCleanText($node->textContent);
        if ($text === '©' || str_starts_with($text, '©') || $text === 'Pokročilá nastavení') break;
        $links = [];
        foreach ($xp->query('.//a[@href]', $node) as $a) {
            $href = trim($a->getAttribute('href'));
            if ($href !== '' && !str_starts_with($href, '#') && !preg_match('/^(mailto|javascript):/i', $href)) {
                $links[] = bavlnkaAbsoluteUrl($href, $pageUrl);
            }
        }
        $nodes[] = ['tag' => $node->nodeName, 'text' => $text, 'links' => $links];
    }

    // How the page's sentences capitalise words, to turn the all-capitals headings back into normal case.
    $known = [];
    foreach ($nodes as $node) {
        if ($node['tag'] !== 'p') continue;
        foreach (preg_split('/[\s,.–()]+/u', $node['text']) as $word) {
            if (preg_match('/^\p{Lu}\p{Ll}+$/u', $word)) $known[mb_strtolower($word)] = $word;
        }
    }

    $sections = [];
    $lastYear = null;
    $block = null;
    $competition = null;
    $part = null;
    $label = null;
    $pending = null;
    $skipping = false;
    $inBavlnka = false;

    $flush = function () use (&$pending, &$sections, &$block, &$competition, &$part, &$label) {
        if ($pending === null || (!$pending['winners'] && !$pending['url'])) {
            $pending = null;
            return;
        }
        [$b, $c] = [$block, $competition];
        if ($b === null) {
            $pending = null;
            return;
        }
        $title = array_filter([$part, $label, $pending['part']]);
        $previous = $c === null ? end($sections[$b]['events']) : end($sections[$b]['children'][$c]['events']);
        $event = [
            // Parts of a tournament (final, overall winners) are listed without a date of their own.
            'date' => $pending['date'] ?? ($previous['date'] ?? null),
            'title' => $title ? implode(' – ', $title) : null,
            'url' => $pending['url'],
            'winner' => $pending['winners'] ? ['kind' => $pending['team'] ? 'team' : 'pair', 'entries' => $pending['winners']] : null,
            'part' => $part,
        ];
        if ($c === null) {
            $sections[$b]['events'][] = $event;
        } else {
            $sections[$b]['children'][$c]['events'][] = $event;
        }
        $pending = null;
    };
    $start = function (string $sentence = '') use (&$pending, &$lastYear, &$season) {
        $date = null;
        if (preg_match('/(\d{1,2})\.\s*(\d{1,2})\.(?:\s*(\d{4}))?/u', $sentence, $m)) {
            $date = resultsImportDate((int) $m[1], (int) $m[2], isset($m[3]) && $m[3] !== '' ? (int) $m[3] : null, $lastYear, $season);
            if ($date) $lastYear = (int) substr($date, 0, 4);
        }
        $partOf = null;
        if (preg_match('/(\d+)\.\s*(část|kol)/u', $sentence, $m)) $partOf = $m[2] === 'kol' ? "$m[1]. kolo" : "$m[1]. část";
        $pending = ['date' => $date, 'part' => $partOf, 'winners' => [], 'team' => (bool) preg_match('/družstv|tým/u', $sentence),
            'url' => null, 'members' => false];
    };

    foreach ($nodes as $node) {
        ['tag' => $tag, 'text' => $text, 'links' => $links] = $node;

        if ($tag === 'h1') {
            $flush();
            if (preg_match('/SEZÓNY\s+(\d{4})\/(\d{2})/u', $text, $m)) $season = "$m[1]-$m[2]";
            $skipping = (bool) array_filter(RESULTS_IMPORT_SKIP, fn($re) => preg_match($re, $text));
            $competition = $part = $label = null;
            if ($skipping) {
                $block = null;
                continue;
            }
            $inBavlnka = (bool) preg_match('/BAVLN/u', $text);
            $sections[] = ['title' => resultsImportHeading($text, $known), 'kind' => $inBavlnka ? 'bavlnka' : 'events',
                'events' => [], 'children' => [], 'note' => null];
            $block = array_key_last($sections);
            continue;
        }
        if ($skipping || $block === null) continue;

        if ($tag === 'h2') {
            $flush();
            $part = $label = null;
            // Months only split a long list of tournaments, as with Bavlnka.
            if ($inBavlnka || in_array(mb_strtolower($text), RESULTS_IMPORT_MONTHS, true)) {
                $competition = null;
                continue;
            }
            $sections[$block]['children'][] = ['title' => resultsImportHeading($text, $known), 'kind' => 'events',
                'events' => [], 'note' => null, 'source' => $text];
            $competition = array_key_last($sections[$block]['children']);
            continue;
        }

        if ($tag === 'h3') {
            $flush();
            $label = null;
            if (preg_match('/CELKOVÉ POŘADÍ/u', $text)) {
                $part = null;
            } elseif (preg_match('/^CELKOV/u', $text) && $part) {
                $part = "$part – " . mb_strtolower(resultsImportHeading($text, $known));
            } else {
                $part = resultsImportHeading($text, $known);
            }
            continue;
        }

        // Paragraphs
        if (preg_match('/^(Vítěz|Mistr)/u', $text)) {
            $flush();
            // "Vítězem turnaje hraného …" is a tournament of its own, not one more part of the one above.
            if (preg_match('/^Vítěz\p{L}*\s+turnaje\s+hran/u', $text)) $part = $label = null;
            $start($text);
            continue;
        }
        if (str_starts_with($text, '🏆')) {
            if ($pending === null || $pending['url'] !== null) {
                $flush();
                $start();
            }
            $winner = trim(mb_substr($text, 1));
            $players = $pending['team'] ? null : resultsImportPlayers($winner);
            if ($players === null) {
                $pending['team'] = true;
                $pending['winners'][] = ['name' => $winner];
            } else {
                $pending['winners'][] = ['players' => $players];
            }
            continue;
        }
        if ($text === 'v sestavě') {
            if ($pending) $pending['members'] = true;
            continue;
        }
        if ($pending && $pending['members'] && !$links) {
            $last = array_key_last($pending['winners']);
            if ($last !== null) $pending['winners'][$last]['members'] = $text;
            $pending['members'] = false;
            continue;
        }
        if ($links) {
            if ($pending === null || $pending['url'] !== null) {
                $flush();
                $start();
            }
            $pending['url'] = $links[0];
            $flush();
            continue;
        }
        if (preg_match('/(Soutěž začíná|Soutěž se hraje|Termín zahájení)/u', $text)) {
            $flush();
            $note = ['text' => $text, 'part' => $part];
            if ($competition !== null) {
                $sections[$block]['children'][$competition]['notes'][] = $note;
            } else {
                $sections[$block]['notes'][] = $note;
            }
            continue;
        }
        // A capitals line such as "PONDĚLÍ" names what the next link is for.
        if (resultsImportIsCaps(explode(' ', $text)[0] ?? '') && mb_strlen($text) < 60) {
            $flush();
            $label = preg_match('/CELKOVÉ POŘADÍ/u', $text) ? null : resultsImportHeading($text, $known);
        }
    }
    $flush();

    return ['season' => $season, 'sections' => $sections];
}

/** Skupinovka slug in a link to our own presentation, on either domain. */
function resultsImportTournamentSlug(?string $url): ?string
{
    if ($url && preg_match('#vysledky\.(?:bkpraha\.cz|zdenektomis\.eu)/tournament/([a-z0-9-]+)#i', $url, $m)) return strtolower($m[1]);
    return null;
}

/**
 * Turns the parsed page into this app's sections: skupinovka parts linking to our own tournaments become
 * sections that show the live standings, přebory and Bavlnka point to what the app stores.
 */
function resultsImportPlan(array $parsed, string $season, array $tournamentSlugs, array $preborSlugs, int $bavlnkaStored): array
{
    $plan = [];
    foreach ($parsed['sections'] as $block) {
        $top = ['title' => $block['title'], 'kind' => $block['kind'], 'ref' => null, 'note' => null, 'events' => [], 'children' => []];
        // Bavlnka comes from its own tables only where they hold the season; older seasons keep the page's list.
        if ($block['kind'] === 'bavlnka' && $bavlnkaStored < count($block['events']) - 1) $top['kind'] = 'events';
        if ($top['kind'] !== 'bavlnka') $top['events'] = $block['events'];
        $top['note'] = isset($block['notes']) ? $block['notes'][0]['text'] : null;

        foreach ($block['children'] as $child) {
            $source = $child['source'];
            $prebor = null;
            if (preg_match('/^(TOPOV|IMPOV)/u', $source, $m)) {
                $prefix = str_starts_with($m[1], 'TOP') ? 'top' : 'imp';
                $prebor = $preborSlugs["$prefix-" . substr($season, 0, 4)] ?? null;
            }
            if ($prebor) {
                $top['children'][] = ['title' => $child['title'], 'kind' => 'prebor', 'ref' => $prebor, 'note' => null, 'events' => []];
                continue;
            }

            // Parts that are our own skupinovky, or only say when they start, get a section of their own.
            $rest = [];
            $splitParts = [];
            foreach ($child['events'] as $event) {
                $slug = resultsImportTournamentSlug($event['url']);
                if ($slug && in_array($slug, $tournamentSlugs, true) && $event['part']) {
                    $splitParts[$event['part']] = ['title' => "{$child['title']} – " . mb_strtolower($event['part']), 'kind' => 'tournament',
                        'ref' => $slug, 'note' => null, 'events' => []];
                } else {
                    $rest[] = $event;
                }
            }
            foreach ($child['notes'] ?? [] as $note) {
                if ($note['part'] && $splitParts) {
                    $splitParts[$note['part']] = ['title' => "{$child['title']} – " . mb_strtolower($note['part']), 'kind' => 'tournament',
                        'ref' => null, 'note' => $note['text'], 'events' => []];
                } elseif ($note['part']) {
                    $rest[] = ['date' => null, 'title' => $note['part'], 'url' => null, 'winner' => ['kind' => 'text', 'text' => $note['text']], 'part' => $note['part']];
                }
            }
            $plainNote = array_values(array_filter($child['notes'] ?? [], fn($n) => !$n['part']))[0]['text'] ?? null;
            if ($rest || !$splitParts || $plainNote) {
                $top['children'][] = ['title' => $child['title'], 'kind' => 'events', 'ref' => null, 'note' => $plainNote, 'events' => $rest];
            }
            array_push($top['children'], ...array_values($splitParts));
        }
        if ($top['children'] && $top['kind'] === 'events' && !$top['events']) $top['kind'] = 'group';
        // A block with both its own tournaments and competitions under it keeps its tournaments in a first section.
        if ($top['children'] && $top['events']) {
            array_unshift($top['children'], ['title' => $top['title'], 'kind' => 'events', 'ref' => null, 'note' => null, 'events' => $top['events']]);
            $top['events'] = [];
            $top['kind'] = 'group';
        }
        $plan[] = $top;
    }
    return $plan;
}

/**
 * Imports one bkpraha.cz results page into an empty season, then reads winners for the tournaments
 * the page names without one, as long as the time budget lasts.
 */
function resultsImport(PDO $pdo, string $season, string $url, ?callable $http = null, int $budget = 45): array
{
    $http ??= 'bavlnkaHttpGet';
    resultsSeasonRange($season);
    if (in_array($season, resultsSeasons($pdo), true)) {
        throw new InvalidArgumentException("Sezóna $season už má sekce, import jde jen do prázdné");
    }
    [$html, $finalUrl, $status] = $http($url);
    if ($status >= 400) throw new InvalidArgumentException("Stránka $url vrátila HTTP $status");

    $parsed = resultsParseListing($html, $finalUrl, $season);
    if ($parsed['season'] && $parsed['season'] !== $season) {
        throw new InvalidArgumentException("Stránka je pro sezónu {$parsed['season']}, ne $season");
    }
    [$from, $to] = resultsSeasonRange($season);
    bavlnkaEnsureSchema($pdo);
    $bavlnka = $pdo->prepare("SELECT COUNT(*) FROM bavlnka_tournaments WHERE status = 'ok' AND date BETWEEN ? AND ?");
    $bavlnka->execute([$from, $to]);
    $tournamentSlugs = $pdo->query("SELECT slug FROM tournaments")->fetchAll(PDO::FETCH_COLUMN);
    $preborSlugs = array_column(preborCompetitions($pdo), 'slug', 'slug');
    $plan = resultsImportPlan($parsed, $season, $tournamentSlugs, $preborSlugs, (int) $bavlnka->fetchColumn());

    // The page of the season being played links running competitions (a league) without a winner yet.
    $running = resultsSeasonOf(date('Y-m-d')) === $season;
    $insertEvent = $pdo->prepare("INSERT INTO results_events (section_id, date, title, url, format, winner, ongoing, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $counts = ['sections' => 0, 'events' => 0];
    $toFetch = [];
    $pdo->beginTransaction();
    try {
        $addEvents = function (int $sectionId, array $events) use ($insertEvent, &$counts, &$toFetch, $pdo, $running) {
            foreach ($events as $event) {
                $insertEvent->execute([$sectionId, $event['date'], $event['title'], $event['url'], $event['winner'] ? 'listing' : null,
                    $event['winner'] ? json_encode($event['winner'], JSON_UNESCAPED_UNICODE) : null,
                    $running && !$event['winner'] ? 1 : 0,
                    $event['winner'] ? 'ok' : ($event['url'] ? 'pending' : 'link')]);
                $counts['events']++;
                if (!$event['winner'] && $event['url']) $toFetch[] = (int) $pdo->lastInsertId();
            }
        };
        foreach ($plan as $top) {
            $id = resultsSaveSection($pdo, ['season' => $season] + array_intersect_key($top, array_flip(['title', 'kind', 'ref', 'note'])));
            $counts['sections']++;
            $addEvents($id, $top['events']);
            foreach ($top['children'] as $child) {
                $childId = resultsSaveSection($pdo, ['season' => $season, 'parent_id' => $id]
                    + array_intersect_key($child, array_flip(['title', 'kind', 'ref', 'note'])));
                $counts['sections']++;
                $addEvents($childId, $child['events']);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    $start = microtime(true);
    $fetched = 0;
    foreach ($toFetch as $id) {
        if (microtime(true) - $start > $budget) break;
        resultsRefetchEvent($pdo, $id, $http);
        $fetched++;
    }
    return $counts + ['fetched' => $fetched, 'notFetched' => count($toFetch) - $fetched];
}
