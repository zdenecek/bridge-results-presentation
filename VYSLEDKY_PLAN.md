# Replacing bkpraha.cz/vysledky – plan

## What the page is today

The page is hand-edited in Webnode. It has one block per section, and each event inside a block is three paragraphs:

> Vítězem turnaje hraného 15. 9. 2026 je pár · 🏆 **Anna VĚRTELÁŘOVÁ** - **Milada FIŠEROVÁ** · [Výsledky](…)

| Section | Where the data lives now | Already in our DB |
| --- | --- | --- |
| Pražská bridžová liga | TC export in `/prezentace/2026/…` (team event, winner = team name) | no |
| Skupinovka A/B per cycle, "Soutěž začíná 30. 11." | our tournaments (`tournaments`) + club calendar | yes |
| TOP / IMP přebor: last round winner + overall standings widget | TC exports + Google Sheet CSV | yes (`prebor_*`) |
| Malé švýcaráky (memoriály) | TC export in `/prezentace/…` | no |
| Bavlnka by month + standings widget | TC on bridge.zdenektomis.eu, sometimes Giborg | yes (`bavlnka_*`) |
| Panonia, Velké ceny (Ústí, Znojmo …), Letní turnaje | Giborg 6f.sk, other clubs, mostly behind 1url.cz | no |
| Archiv 2022/23–2025/26 | separate Webnode pages with the same structure | no |

The homepage repeats the same facts as news blocks, for example "Ve 2. kole Topového přeboru … zvítězil pár …".

Two findings matter for the plan:

- **Webnode accepts HTML blocks with `<style>`/`<script>`.** The current ranking widgets are built this way, and our API already sends `Access-Control-Allow-Origin: *`. Embedding is therefore possible.
- **Bavlnka discovery reads bkpraha.cz/vysledky.** Once that page stops listing tournaments, discovery stops. It has to move to the new source before the switch.

## Status

- **Decided:** no iframe; bkpraha.cz/vysledky will only link to vysledky.bkpraha.cz/vysledky.
  The `/embed/*` parts below are dropped.
- **Done:** step 1 (Bavlnka discovery), step 2 (backend, `api/results.php`), step 4 (public page,
  now the home page `/`) and step 6 (admin tab Výsledky, without the inbox of new TC folders).
- **Done too:** step 3, the import (`api/results_import.php`).
- **Left:** the inbox of new TC folders, the switch on bkpraha.cz.

## Target

`vysledky.bkpraha.cz/vysledky` (and `/vysledky/2025-26` … for archives) shows the whole season. It is generated from the data we already have, plus one new kind of record for everything else. bkpraha.cz/vysledky shows it embedded. After each event the only manual step is pasting the results URL into the admin. The winner, date and links fill themselves in.

## Design

### Data

- **`results_sections`**: one row per section:
  - `season`, `parent` (for h1/h2 nesting), `title` and `order`
  - `kind`, which sets where the section's entries come from:
    - `skupinovka`: a list of tournaments, showing the leader after the last round, or the start date from the calendar
    - `prebor`: one competition, showing the last-round winner and the embedded standings
    - `bavlnka`: entries grouped by month, plus the standings
    - `events`: generic entries
- **`results_events`**: generic entries:
  - `section_id`, `date`, `title` (optional, e.g. "Hlavní párový turnaj – 1. část") and `url` (resolved, no 1url)
  - `format` (tc / giborg / link), the winner (`winner_kind` pair|team) and `winner_players` (JSON)
  - `winner_override` (free text) and `status`
- **Winner extraction:** reuse `bavlnkaFetchResults`; Place 1 gives the players or the TC team `_name`. Tied first places list every pair. For a link we can't parse, the winner is typed in.
- **Season:** derived from the date (September–August). The admin chooses which season is "current".

### Page

- **New public route `/vysledky` in this app.** The sections render in order and the styling mimics the Webnode look: green `#1A881A` and surnames in capitals.
- **Embed variant `/embed/vysledky`.** No header or footer, a transparent background, and a small script that posts its height to the parent page for auto-resize.
- **On Webnode,** Ondra replaces the content of `/vysledky/` with one HTML block: an iframe plus the 10-line resize listener. The URL bkpraha.cz/vysledky stays.
- **Optional second embed `/embed/novinky`.** These are "latest results" cards for the homepage: the last winner of each running competition, built from the same data.
- **The ranking CSV widgets become unnecessary**, because the standings are part of the embedded page. The CSV endpoints stay for anyone still using them.

### Admin ("Výsledky" tab)

- **Season picker, then sections.** Sections can be added, renamed and reordered (up/down links), and the next season can be started by copying this season's structure.
- **Add an event.** You choose a section, paste a URL and optionally a date. It fetches straight away and shows the parsed winner, which can be overridden.
- **Inbox.** New TC folders found on disk under `/prezentace/<year>/` (the API runs on the same docroot), and Bavlnka folders on bridge.zdenektomis.eu if a listing is possible, appear as "nezařazené". Assigning one to a section takes one click.
- **Skupinovka, přebor and Bavlnka sections** need nothing per event; they read their own tables.

### Import of what exists

- **One-off import of the current page and archives 2022/23–2025/26.** It generalises `bavlnkaParseListing` to every h1/h2 section and reads the paragraphs: date, 🏆 winner and link. It resolves 1url.cz and creates sections and events. The 2022/23 links to old Webnode subpages (`/pt-2022-09-14/`) stay as plain links.
- **Check the import** against the pages by eye, season by season. The admin can fix leftovers.

## Steps

1. **Bavlnka discovery off bkpraha.cz.** Bavlnka tournaments get added the same way as any event (inbox / URL in admin), and the scraper is kept only for the import. This step is needed before the switch.
2. **Backend:** the tables, the winner extraction for TC pairs and teams and for Giborg, the section/event CRUD, and `GET /api/results/{season}` with everything the page needs in one response. Tests use saved pages from all three formats.
3. **Import script** for the current page and the 4 archive pages, then review.
4. **Public page** `/vysledky` and `/vysledky/{season}`, and the archive list.
5. **Embed:** `/embed/vysledky` with auto-height, tested inside a local HTML page that mimics Webnode, then handed to Ondra as a snippet.
6. **Admin "Výsledky"** with the inbox.
7. **Switch:** Ondra swaps the Webnode content and archive pages for the embed and removes the CSV widgets. Afterwards, check the page on a phone.
8. *(optional)* Homepage `/embed/novinky`, and "Soutěž začíná …" generated from the club calendar (the first `A1`/`B1` event after the previous cycle).

Steps 1–4 can ship without touching bkpraha.cz. The page just lives at vysledky.bkpraha.cz/vysledky until the switch.

## Open questions

- **Embed or link.** An iframe keeps the bkpraha.cz URL and look. A plain menu link to vysledky.bkpraha.cz/vysledky is less work and nothing breaks if Webnode changes. Recommendation: iframe, because people and the homepage already link to /vysledky/.
- **Who adds events.** If it's only you, the admin can stay minimal. If Ondra or Kevin add Velké ceny, the add form and inbox need to be foolproof.
- **External events** (Velké ceny in other cities, Panonia). Keep them on the page? They make up most of the 1url links and can't be auto-discovered, so they will always need a pasted URL.
- **Homepage news.** Replace it with `/embed/novinky` too, or keep it hand-written as editorial content.
