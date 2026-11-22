import { describe, expect, it } from "vitest";
import { buildCalendar, pragueTime } from "@/utils/calendar";

describe("calendar file", () => {
    it("turns Prague wall-clock time into UTC in winter and in summer", () => {
        expect(pragueTime("2026-11-23", "17:45").toISOString()).toBe("2026-11-23T16:45:00.000Z");
        expect(pragueTime("2026-05-11", "17:45").toISOString()).toBe("2026-05-11T15:45:00.000Z");
        expect(pragueTime("2027-03-28", "17:45").toISOString()).toBe("2027-03-28T15:45:00.000Z");
        expect(pragueTime("2026-10-25", "17:45").toISOString()).toBe("2026-10-25T16:45:00.000Z");
    });

    it("writes timed and all-day events with escaped text", () => {
        const ics = buildCalendar([
            {
                uid: "a-1@vysledky.bkpraha.cz",
                summary: "Skupinová A, 5. kolo: Krása - Krása",
                description: "Stůl 3; NS\nvýsledky",
                location: "Bridžový klub Praha, Italská 209/17, Praha 2",
                start: pragueTime("2026-11-23", "17:45"),
                end: pragueTime("2026-11-23", "21:45"),
            },
            { uid: "a-2@vysledky.bkpraha.cz", summary: "Dohrávka", day: "2026-12-31" },
        ], new Date("2026-11-22T12:00:00Z"));
        const lines = ics.split("\r\n");
        expect(lines[0]).toBe("BEGIN:VCALENDAR");
        expect(ics.endsWith("END:VCALENDAR\r\n")).toBe(true);
        expect(lines).toContain("DTSTART:20261123T164500Z");
        expect(lines).toContain("DTEND:20261123T204500Z");
        expect(lines).toContain("DTSTAMP:20261122T120000Z");
        expect(lines).toContain("SUMMARY:Skupinová A\\, 5. kolo: Krása - Krása");
        expect(lines).toContain("DESCRIPTION:Stůl 3\\; NS\\nvýsledky");
        expect(lines).toContain("DTSTART;VALUE=DATE:20261231");
        expect(lines).toContain("DTEND;VALUE=DATE:20270101");
        expect(ics.match(/BEGIN:VEVENT/g)).toHaveLength(2);
    });

    it("folds lines longer than 75 octets without splitting characters", () => {
        const ics = buildCalendar([{ uid: "x", summary: "Žluťoučký kůň úpěl ďábelské ódy ".repeat(4), day: "2026-11-23" }]);
        const lines = ics.split("\r\n");
        const encoder = new TextEncoder();
        lines.forEach((line) => expect(encoder.encode(line).length).toBeLessThanOrEqual(75));
        expect(ics.replace(/\r\n /g, "").split("\r\n")).toContain("SUMMARY:" + "Žluťoučký kůň úpěl ďábelské ódy ".repeat(4));
    });
});
