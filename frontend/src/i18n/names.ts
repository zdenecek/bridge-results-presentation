/*
 * Names entered in the admin (tournaments, results sections, championships, the invitation) are Czech
 * and may have an English variant. Without one, English pages show a suggestion made from the club's
 * usual names; words it does not know stay as they are, so a memorial keeps its name.
 */

const CITIES: Record<string, string> = {
    "Prahy": "Prague",
    "Znojma": "Znojmo",
    "Havířova": "Havířov",
    "Pardubic": "Pardubice",
    "Českých Budějovic": "České Budějovice",
};

const RULES: [RegExp, string | ((...m: string[]) => string)][] = [
    [/^Velká cena (.+)$/u, (_, city) => `Grand Prix ${CITIES[city] ?? city}`],
    [/^Velké ceny$/u, "Grand Prix tournaments"],
    [/Pražská bridžová liga/u, "Prague Bridge League"],
    [/Párové turnaje Bavlnka/u, "Tuesday Pairs"],
    [/^Bavlnka/u, "Tuesday Pairs"],
    [/Bridžový týden (\S+)/u, "$1 Bridge Week"],
    [/Topový přebor BKP|TOPový přebor BK Praha/iu, "BKP Top Pairs Championship"],
    [/Impový přebor BKP|IMPový přebor BK Praha/iu, "BKP IMP Championship"],
    [/Švýcarská skupinovka/u, "Swiss Group League"],
    [/Malé skupinovky/u, "Small group leagues"],
    [/Malé švýcaráky/u, "Small Swiss tournaments"],
    [/Malý švýcarák/iu, "Small Swiss"],
    [/Skupinov(?:á|ka) ([A-Z])\b/u, "Group League $1"],
    [/Skupinovky/u, "Group leagues"],
    [/Hlavní párový turnaj/u, "Main pairs tournament"],
    [/Vedlejší párový turnaj/u, "Side pairs tournament"],
    [/Vedlejší týmový turnaj|Vedlejší turnaj družstev/u, "Side teams tournament"],
    [/Turnaj družstev|Týmový turnaj/u, "Teams tournament"],
    [/Letní párové turnaje/u, "Summer pairs tournaments"],
    [/Pondělní párové turnaje/u, "Monday pairs tournaments"],
    [/Pondělní cyklus/u, "Monday series"],
    [/Úterní cyklus/u, "Tuesday series"],
    [/Česko-německý turnaj/u, "Czech–German tournament"],
    [/(\d+)\. část/u, "part $1"],
    [/(\d+)\. kolo/u, "round $1"],
    [/\bPodzim\b|\bpodzim\b/u, "Autumn"],
    [/\bZima\b|\bzima\b/u, "Winter"],
    [/\bJaro\b|\bjaro\b/u, "Spring"],
    [/\bLéto\b|\bléto\b/u, "Summer"],
    [/^Vánoční$/u, "Christmas"],
    [/^Závěrečná$/u, "Closing"],
    [/^Úvodní$/u, "Opening"],
];

export function suggestEnglish(name: string): string {
    let result = name;
    for (const [pattern, replacement] of RULES) {
        result = typeof replacement === "string"
            ? result.replace(pattern, replacement)
            : result.replace(pattern, replacement as (...m: string[]) => string);
    }
    return result;
}

/** The name to show: Czech as entered, in English the English variant or the suggestion. */
export function localName(lang: string, czech: string, english?: string | null): string {
    if (lang !== "en") return czech;
    return english?.trim() || suggestEnglish(czech);
}

function englishDate(day: string, month: string, year: string): string {
    return new Date(Number(year), Number(month) - 1, Number(day))
        .toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
}

const NOTE_RULES: [RegExp, (...m: string[]) => string][] = [
    [/^Soutěž začíná (\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})\.?$/u, (_, d, m, y) => `The competition starts on ${englishDate(d, m, y)}.`],
    [/^Termín zahájení bude upřesněn\.?$/u, () => "The start date will be announced."],
    [/^Soutěž zatím nezačala\.?$/u, () => "The competition has not started yet."],
];

/** A results section note: Czech as entered; in English the club's usual notes are translated, others stay Czech. */
export function localNote(lang: string, czech: string): string {
    if (lang !== "en") return czech;
    const note = czech.trim();
    for (const [pattern, replacement] of NOTE_RULES) {
        const match = note.match(pattern);
        if (match) return replacement(...match);
    }
    return czech;
}
