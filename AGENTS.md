# Poznámky pro agenty

Prezentace výsledků skupinovek Bridžového klubu Praha. Vue 3 + Vite frontend,
tenké PHP (Slim) API nad jednou tabulkou `tournaments` (`id, name, slug, data JSON`).

- Provoz: <https://vysledky.bkpraha.cz>, API na `/api/`. `vysledky.zdenektomis.eu`
  je starý docroot téhož – viz Nasazení na produkci.
- Klub a propozice: <https://bkpraha.cz>, kalendář je veřejný Google Calendar
- Matrika ČBS: <https://www.matrikacbs.cz/Prehled-hracu.aspx>

## Vývoj

```bash
cd frontend
npm install
npm run dev          # potřebuje .env s VITE_API_URL
npm run type-check   # vue-tsc, běží i v rámci npm run build
npm test             # vitest, výpočet průměrů a IMPů
npm run build
```

`frontend/.env` v repozitáři není; zkopíruj si ho z hlavního checkoutu nebo
nastav `VITE_API_URL=https://vysledky.bkpraha.cz/api/`, ať máš proti čemu
vyvíjet. Heslo k zápisovým endpointům je `key` v `api/config.php` (necommitnuté).

## API

| metoda | cesta | pozn. |
| --- | --- | --- |
| GET | `/api/tournaments` | jen `id, name, slug` |
| GET | `/api/tournament/{slug\|id}` | celý turnaj |
| POST | `/api/tournament` | hlavička `Apikey` |
| PUT | `/api/tournament/{id}` | hlavička `Apikey` |
| DELETE | `/api/tournament/{id}` | hlavička `Apikey` |

| GET | `/api/bavlnka/ranking` | žebříček Bavlnky (JSON) |
| GET | `/api/bavlnka/ranking.csv` | totéž ve tvaru Google Sheetu, který čte widget na bkpraha.cz |
| GET/POST | `/api/bavlnka/update` | hlavička `Apikey` nebo `?key=` |
| GET | `/api/bavlnka/admin` | hlavička `Apikey` |
| GET | `/api/prebor` | seznam přeborů (`slug, season, title, …`) |
| GET | `/api/prebor/{slug}/ranking` | celkové pořadí přeboru (JSON) |
| GET | `/api/prebor/{slug}/ranking.csv` | totéž ve tvaru Google Sheetu pro widget na bkpraha.cz |
| GET/POST | `/api/prebor/update` | hlavička `Apikey` nebo `?key=` (stejný klíč jako Bavlnka) |
| GET | `/api/prebor/admin` | hlavička `Apikey`, zápisy přes `/admin/prebor` |

`POST /api/transcribe-slip` (hlavička `Apikey`, tělo `{image, mimeType}` s fotkou
v base64) pošle fotku lístečku do Gemini a vrátí `{text}` s řádky pro zápis
dohrávky. Klíč je v GitHub secretu `GEMINI_API_KEY`, deploy z něj zapíše
`api/gemini-config.php` (ten má přednost před `gemini` v `config.php`). Modely
se zkouší postupně (`gemini-flash-latest`, `gemini-3.5-flash`,
`gemini-3.5-flash-lite`), další se použije, když je předchozí přetížený, bez
kvóty nebo zrušený. Lite čte výrazně hůř, proto editor ukazuje, který model četl.
Nevyzkoušený nápad: v promptu nechat model kontrolovat každý řádek proti zapsanému
skóre a při neshodě přečíst barvu znovu – na Flash-Lite to v jednom pokusu snížilo
chyby měnící body ze 4 na 2.

Deploy nahrává i `api/` kromě `vendor/` a `config.php` (DB a heslo zůstávají jen
na serveru). Před nahráním ověří, že na serveru existuje `api/public`.

Dvě pasti:

- `data` posílej jako **objekt**, ne jako JSON řetězec. PHP dělá
  `json_encode($data['data'])`, takže z řetězce vznikne dvojitě zakódovaný JSON
  a frontend ho pak nepřečte.
- GET turnaje má `Cache-Control: max-age=540`. Po zápisu ověřuj s parametrem
  navíc (`?cb=…`), jinak devět minut čteš starou verzi. Frontend to dělá sám:
  editor turnaje načítá vždy čerstvě a prohlížeč, ze kterého se 10 minut zpátky
  ukládalo, přidává `?cb=` ke každému načtení turnaje.

## Datový model turnaje

```jsonc
{
  "title": "Skupinová A Podzim 2026",
  "slug": "skupinova-a-podzim-2026",
  "totalRounds": 5,
  "td": { "name": "…", "email": "…" },
  "groups":    [ { "name": "A1", "players": [1, 2, 3, 4, 5, 6] } ],
  "players":   { "1": { "id": 1, "title": "Vozábal - Klemš",
                        "players": [ { "name": "Vozábal David", "id": "1868", "club": "BKP" } ] } },
  "rotations": { "1": { "1": { "ns": 1, "ew": 6 } } },   // kolo -> stůl -> pár
  "rounds":    { "1": { "date": "2026-09-21", "boardResults": [], "boards": {},
                        "overwrites": [ { "type": "postponed", "table": 5 } ] } }
}
```

- Čísla párů jdou postupně napříč skupinami v pořadí nasazení, stoly taktéž.
- `settings.rankByAverage` se u novějších ročníků nepoužívá, vynech ho.
- **Pauza (bye)**: pár `{ "id": N, "isBye": true, "title": "pauza", "players": [] }`.
  Je v `rotations`, ale **není** v `groups[].players` – jinak by se počítal do
  velikosti skupiny. Patří na poslední místo ve skupině.
- **Hráč bez matriky**: nech `name` a `id` vynech. `Tournament.toMatrikaString()`
  na to spoléhá – u páru, kde někdo nemá `id`, exportuje místo čísel název páru.
- **Odklad** v kole je `overwrites: [{ "type": "postponed", "table": N }]`.
  Bez výsledků se jen vypíše „Odloženo" v nasazení, což se hodí předvyplnit.
- Kolo bez výsledků (`boardResults: []`) neposouvá `standing`, takže je bezpečné
  založit všechna kola dopředu jen s datem.
- **Dohrávka proti Jackovi** je v `postponedSessions[]` (záložka „Dohrávky“
  v editoru): rozdání a výsledky Jacka z PBN (`field`) a zápisy dohrávek
  (`matches[]` s kolem, stolem a textem lístečku). Průměr rozdání počítá
  `trimmedDatum` z Jacka i všech zápisů (z každé strany se ořízne 10 % váhy,
  zaokrouhlení na desítky), IMPy se propíšou do `postponed` overwritu daného
  stolu i s odkazem `session`.

## Rozpisy (movements)

`frontend/src/model/createTournament.ts` má tabulky pro skupinovku A a B.
Podporované velikosti skupiny jsou **6, 8 a 10** – lichý počet dvojic se řeší
pauzou. A má 5 kol, B 9 kol (u osmičlenné skupiny v B jich tabulka dává jen 7 a
zbytek se dříve dopisoval ručně).

Parita se bere z **koncových číslic názvu skupiny**: končí-li `1`, použije se
`odd`, jinak `even`. Takže A1, A21, A31 jsou `odd`, A22, A32 `even`; u B jsou obě
tabulky stejné. Pozice ve skupině tedy určuje, kdo s kým hraje – při odkladech se
dvojice nasazují tak, aby se v 1. kole potkaly navzájem (nebo dostaly pauzu).

## Nasazení nového cyklu

Podklady chodí jako Google Sheet „Přihlášky Skupinovka A/B <období>" (nasazení
dělá Kevin, organizuje Ondřej Krása). Listy: přihlášky (dvojice + celá jména +
vklady), skrytý `BKP` (jmenný seznam) a `Nasazení` (PŘIHLÁŠENÁ DVOJICE /
PŘEDCHOZÍ DVOJICE / UMÍSTĚNÍ / NASAZENÍ / STARTOVNÍ ČÍSLO / POZNÁMKA). Sešity jsou
čitelné bez přihlášení přes `…/export?format=csv&gid=<gid>`, případně
`format=xlsx` pro všechny listy najednou.

Matriční čísla v sešitu **nejsou**. Zdroje, v tomto pořadí:

1. `conversion-script/Hraci_CBS.csv` (`;`, kódování cp1250, sloupce
   Legitimace / Příjmení / Jméno / Klub) – starší export, ale offline a rychlý
2. dříve zveřejněné turnaje přes API (to umí i formulář `/admin/create`)
3. <https://www.matrikacbs.cz/Prehled-hracu.aspx> pro nováčky

Pozor na jmenovce (dva Jan Martynkové – ten náš je 1893, BKP; dva Vladimírové
Hanákové – 876 a 2209) a na to, že hosté ze zahraničí legitimaci nemají.

Termíny kol jsou v klubovém kalendáři. Je to Google Calendar embed, ICS feed:

```bash
curl -s "https://calendar.google.com/calendar/ical/otdvgn4re212pokvi1ngbcvsk8%40group.calendar.google.com/public/basic.ics"
```

Události `A1`…`A5` a `B1`…`B9` jsou jednotlivá kola (áčko v pondělí, béčko ve
středu, po čtrnácti dnech s výjimkami). Po posledním kole cyklu následuje hned
`A1`/`B1` dalšího cyklu – hlídej, kde je předěl.

Formulář `/admin/create` bere nasazení jako jednu tabulku
`skupina, dvojice, hráč 1, hráč 2, …` (tabulátor, středník nebo čárka), sám
odvodí skupiny a jejich velikosti, řádek `pauza` udělá bye a tlačítkem doplní
matriční čísla podle jmen z minulých turnajů. Odklady a data dalších kol se
dodělávají v editoru turnaje.

## Žebříček Bavlnky

Úterní párové turnaje Bavlnka. `api/bavlnka.php` čte sekci „Párové turnaje Bavlnka"
na <https://www.bkpraha.cz/vysledky/> a archivních stránkách (u každého turnaje
odstavec s datem a odkaz „Výsledky“), odkazy přes 1url.cz rozbalí a stáhne výsledky:

- **Tournament Calculator** (`bridge.zdenektomis.eu/vysledky/<rok>/bkp-bavlnka/MMDDbavlnka/`):
  `results.json.gz`, u starších exportů `results.json`. Datum se bere ze složky, titulek
  stránky se píše ručně a bývá špatně.
- **Giborg/GoodLuck** (`bridgebase.6f.sk/results/ZF/<rok>/RRMMDD/result.php`): HTML tabulka,
  shodné pořadí jako „4-5“, jména „Příjmení Jméno“, hosté často jen příjmením.

Data jsou v tabulkách `bavlnka_tournaments`, `bavlnka_results` a `bavlnka_aliases`,
založí se samy. Jeden běh `update` zpracuje, co stihne za minutu (hosting má limit 90 s),
nejnovější turnaje první; na WEDOSu ho volá plánovaná úloha na
`https://vysledky.bkpraha.cz/api/bavlnka/update?key=…` (každý den v 6:00). Klíč v adrese není
heslo, ale `bavlnkaCronKey()` odvozený z hesla; platí jen pro `update` a celou adresu ukazuje
admin na `/admin/bavlnka`. WEDOS na POST bez těla vrací 411, cron proto volá GET.

Body: 10 za účast + 20 za každou dvojici za sebou, při shodě průměr sdílených míst;
posledních 5 turnajů plně, každých dalších 5 o 10 % méně, celkem posledních 50.
Sčítá se nezaokrouhleně a zaokrouhluje až součet. `api/tests/bavlnka_test.php`
(`php api/tests/bavlnka_test.php`) to ověřuje proti žebříčku Ondry Krásy po 13. 10. 2026.

Jména se slučují přes aliasy (výchozí jsou ruční opravy z původního sešitu), pak bez
ohledu na diakritiku a samotné příjmení k jedinému hráči s tím příjmením. Matriční
čísla z TC se ukládají, ale ke slučování se nepoužívají – u neregistrovaných hráčů
se mezi turnaji mění.

## Přebory (Topový a Impový)

Celosezónní cykly 10 kol, do celkového pořadí se sčítá 8 nejlepších výsledků (propozice §9,
odkazy na stránkách `bkpraha.cz/prihlasky-top` a `/prihlasky-imp`). TOP je párový turnaj v %,
IMP švýcar 6 × 4 rozdání ve VP. Každá sezóna každého přeboru je vlastní záznam v
`prebor_competitions` (adresa `slug` jako `top-2026`, sezóna, název, jednotka, počet kol, kolik
se jich započítává a CSV export tabulky přihlášek); nový se zakládá v `/admin/prebor`.
Ročník 2026/27 se založí sám. Veřejně `/prebor` (seznam) a `/prebor/{slug}`.

Kola se přidávají ručně v adminu: adresa prezentace z Tournament Calculatoru (složky si pojmenovává
vedoucí, datum se proto zadává) se hned stáhne přes parser Bavlnky, nebo se vloží výsledky
řádek po řádku (hráči oddělení „ - “, skóre jako poslední číslo). Ručně šlo 1. kolo IMP 2026,
které se omylem hrálo jako páry a Ondra ho přepočítal na VP. `update` stáhne znovu všechna kola
z TC, aby se projevily pozdější opravy; plánovaná úloha na něj zatím není.

Pořadí je po **přihlášených dvojicích** (2–4 hráči z tabulky přihlášek, načte se tlačítkem). Výsledek
se připíše dvojici, ve které hrají oba hráči, jinak jediné, ve které hraje aspoň jeden (náhradník),
jinak vznikne samostatná dvojice, která se mezi koly slučuje podle hráčů. Jména se porovnávají bez
ohledu na pořadí, diakritiku a „ml.“. Hráči ze dvou různých přihlášených dvojic se v adminu ukážou
ke kontrole a výsledek se dá přiřadit ručně (`prebor_assignments`). Sčítá se zaokrouhlené skóre
kol, při shodě součtu se místo dělí. `php api/tests/prebor_test.php` to ověřuje proti widgetům
po 1. kole TOP i IMP 2026.

## CSS

Tabulky jsou široké a stránka se nesmí posouvat vodorovně:

- každou tabulku obal `<div class="table-scroll">`, scrolluje se jen ona
- nepoužívej `max-width: 100vw` – když je vidět svislý scrollbar, je 100vw širší
  než stránka a samo o sobě to způsobí přetečení
- `overflow-x` na `<table>` nedělá nic, musí být na obalu
- `.flex-column > *` má `margin: auto`, což vypíná stretch; proto tam musí být
  i `max-width: 100%`, jinak se široké dítě roztáhne přes obrazovku
- pod 640 px se zmenšuje odsazení buněk, písmo a ruší se pevné `min-width`
  u sloupce se jménem páru a VP buněk

## Nasazení na produkci

Push do `main`, který sáhne na `frontend/**` nebo `api/**`, spustí
`.github/workflows/deploy.yml` (jde i ručně přes `workflow_dispatch`). Workflow
postaví frontend a nahraje `frontend/dist` FTP mirrorem do `./app`, což je
`/www/domains/vysledky.bkpraha.cz/app`, a `api/` (bez `vendor/` a `config.php`)
do `./api`. Změna samotného workflow deploy
nespustí – cesty ve filtru ji nezahrnují, takže ho dispatchni ručně.

Domény (všechny na jedné IP, každá vlastní docroot):

| doména | role |
| --- | --- |
| `vysledky.bkpraha.cz` | kanonická – appka, API, `/prezentace/` s exporty z Tournament Calculatoru |
| `vysledky.zdenektomis.eu` | starý docroot, přesměrovává se; hotový `.htaccess` je v `deploy/` |
| `bridge.zdenektomis.eu` | osobní stránka Zdeňka Tomise, jiná aplikace, neslučovat |

Do konce září 2026 tu byl rozjezd: CI nahrávalo na `vysledky.zdenektomis.eu`,
zatímco `FRONTEND_ENV` nastavoval `VITE_API_URL` na `vysledky.bkpraha.cz/api/`,
takže klubová doména běžela na starém buildu. Kdyby se to mělo opakovat,
nejjistější je `VITE_API_URL=/api/` – konkatenace v `TournamentApi` to snese
a build pak není vázaný na hostname.

Nahrává se přes `lftp` volané přímo z kroku, ne přes akci
`airvzxf/ftp-deployment-action`: ta je rozbitá ve všech verzích (tag `latest`
zmizel, v2.11.14 má nevalidní `action.yml` a Dockerfile všech v2.\* připíná
`ca-certificates=20260611-r0`, které v Alpine v3.24 už není). Kdyby to někdo
chtěl vrátit zpět na akci, musí upstream nejdřív ten pin zvednout.

Mirror běží **bez** `--delete`, takže na serveru nic nemaže; staré assety tam
zůstávají ležet.
