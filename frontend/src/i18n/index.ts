import { computed, ref } from "vue";
import { createI18n } from "vue-i18n";
import cs from "./cs";
import en from "./en";

export { detectLanguage, LANGS } from "./detect";
export type { Lang } from "./detect";
import { detectLanguage, type Lang } from "./detect";

const STORAGE_KEY = "lang";

function readStored(): string | null {
    try {
        return localStorage.getItem(STORAGE_KEY);
    } catch {
        return null;
    }
}

function store(lang: Lang) {
    try {
        localStorage.setItem(STORAGE_KEY, lang);
    } catch {
        // Blocked storage: the choice lasts until the page is reloaded.
    }
}

const initial = detectLanguage(window.location.search, readStored(), navigator.languages ?? [navigator.language]);
if (new URLSearchParams(window.location.search).has("lang")) store(initial);

/** What the visitor chose; the admin and print pages show Czech whatever it is. */
export const chosenLanguage = ref<Lang>(initial);

export const i18n = createI18n({
    legacy: false,
    locale: initial,
    fallbackLocale: "cs",
    messages: { cs, en },
});

export const language = computed(() => i18n.global.locale.value as Lang);

function apply(lang: Lang) {
    i18n.global.locale.value = lang;
    document.documentElement.lang = lang;
}

export function setLanguage(lang: Lang) {
    chosenLanguage.value = lang;
    store(lang);
    apply(lang);
}

/** Called on every navigation: Czech-only pages force Czech without forgetting the visitor's choice. */
export function applyRouteLanguage(routeName: string) {
    const czechOnly = routeName.startsWith("admin") || routeName.startsWith("print");
    apply(czechOnly ? "cs" : chosenLanguage.value);
}

export const t = i18n.global.t;

function toDate(date: Date | string): Date {
    if (date instanceof Date) return date;
    const [year, month, day] = date.split("-").map(Number);
    return new Date(year!, (month ?? 1) - 1, day ?? 1);
}

/** "2. 11. 2026" in Czech, "2 Nov 2026" in English; short leaves out the year. */
export function formatDate(date: Date | string | null | undefined, short = false): string {
    if (!date) return "";
    const d = toDate(date);
    if (language.value === "en") {
        return d.toLocaleDateString("en-GB", short ? { day: "numeric", month: "short" } : { day: "numeric", month: "short", year: "numeric" });
    }
    return short ? `${d.getDate()}. ${d.getMonth() + 1}.` : d.toLocaleDateString("cs-CZ");
}

export function formatNumber(value: number, options?: Intl.NumberFormatOptions): string {
    return value.toLocaleString(language.value === "en" ? "en-GB" : "cs-CZ", options);
}

export function monthName(month: number): string {
    const name = new Date(2000, month - 1, 1).toLocaleDateString(language.value === "en" ? "en-GB" : "cs-CZ", { month: "long" });
    return name.charAt(0).toLocaleUpperCase() + name.slice(1);
}
