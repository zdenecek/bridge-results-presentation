import { describe, expect, it } from "vitest";
import { detectLanguage } from "../src/i18n/detect";
import { suggestEnglish } from "../src/i18n/names";

describe("detectLanguage", () => {
    it("takes the address first", () => {
        expect(detectLanguage("?lang=en", "cs", ["cs-CZ"])).toBe("en");
    });

    it("then the visitor's choice", () => {
        expect(detectLanguage("", "en", ["cs-CZ"])).toBe("en");
    });

    it("then the browser, Slovak reading Czech", () => {
        expect(detectLanguage("", null, ["de-DE", "en-US"])).toBe("en");
        expect(detectLanguage("", null, ["sk-SK", "en"])).toBe("cs");
    });

    it("and Czech when nothing matches", () => {
        expect(detectLanguage("?lang=de", "fr", ["de-DE"])).toBe("cs");
    });
});

describe("suggestEnglish", () => {
    it.each([
        ["Skupinová A Podzim 2026", "Group League A Autumn 2026"],
        ["Skupinovka B", "Group League B"],
        ["Topový přebor BKP 2026/27", "BKP Top Pairs Championship 2026/27"],
        ["Velká cena Prahy", "Grand Prix Prague"],
        ["Velká cena Ústí nad Labem", "Grand Prix Ústí nad Labem"],
        ["Hlavní párový turnaj – 1. část", "Main pairs tournament – part 1"],
        ["Párové turnaje Bavlnka", "Tuesday Pairs"],
        ["Memoriál Tomáše Etlíka", "Memoriál Tomáše Etlíka"],
    ])("%s", (czech, english) => {
        expect(suggestEnglish(czech)).toBe(english);
    });
});
