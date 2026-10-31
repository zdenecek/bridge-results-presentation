export type Lang = "cs" | "en";
export const LANGS: Lang[] = ["cs", "en"];

export function isLang(value: unknown): value is Lang {
    return value === "cs" || value === "en";
}

/**
 * The language a visitor gets: one asked for in the address (?lang=en, to send a link), then their own
 * earlier choice, then the first of the browser's languages we have (the same list the browser sends
 * as Accept-Language; Slovak reads Czech), and Czech when nothing matches.
 */
export function detectLanguage(search: string, stored: string | null, browser: readonly string[]): Lang {
    const asked = new URLSearchParams(search).get("lang");
    if (isLang(asked)) return asked;
    if (isLang(stored)) return stored;
    for (const tag of browser) {
        const base = tag.toLowerCase().split("-")[0];
        if (base === "cs" || base === "sk") return "cs";
        if (base === "en") return "en";
    }
    return "cs";
}

