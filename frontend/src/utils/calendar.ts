/** Calendar files (iCalendar, RFC 5545) for downloading into Google, Apple or Outlook calendars. */

export type CalendarEvent = {
    /** Stable across downloads, so importing again updates the event instead of adding a copy. */
    uid: string;
    summary: string;
    description?: string;
    location?: string;
    url?: string;
} & ({ start: Date; end: Date } | { day: string });

const TIME_ZONE = "Europe/Prague";

/** The instant a Prague wall-clock time happens, e.g. `pragueTime("2026-11-23", "17:45")`. */
export function pragueTime(day: string, time: string): Date {
    const [y, m, d] = day.split("-").map(Number) as [number, number, number];
    const [hh, mm] = time.split(":").map(Number) as [number, number];
    const asUtc = Date.UTC(y, m - 1, d, hh, mm);
    // Prague's offset at that moment; checking twice settles the hours around a DST change.
    let instant = asUtc - offset(new Date(asUtc));
    instant = asUtc - offset(new Date(instant));
    return new Date(instant);
}

function offset(date: Date): number {
    const parts = Object.fromEntries(
        new Intl.DateTimeFormat("en-GB", {
            timeZone: TIME_ZONE, hourCycle: "h23",
            year: "numeric", month: "2-digit", day: "2-digit", hour: "2-digit", minute: "2-digit", second: "2-digit",
        }).formatToParts(date).map((p) => [p.type, p.value]),
    );
    const local = Date.UTC(+parts.year!, +parts.month! - 1, +parts.day!, +parts.hour!, +parts.minute!, +parts.second!);
    return local - Math.floor(date.getTime() / 1000) * 1000;
}

function utcStamp(date: Date): string {
    return date.toISOString().replace(/[-:]/g, "").replace(/\.\d{3}/, "");
}

function escapeText(text: string): string {
    return text.replace(/\\/g, "\\\\").replace(/;/g, "\\;").replace(/,/g, "\\,").replace(/\r?\n/g, "\\n");
}

/** Lines longer than 75 octets continue on the next line after a space. */
function fold(line: string): string {
    const bytes = new TextEncoder();
    const out: string[] = [];
    let current = "";
    for (const char of line) {
        if (bytes.encode(current + char).length > (out.length ? 74 : 75)) {
            out.push(current);
            current = "";
        }
        current += char;
    }
    out.push(current);
    return out.join("\r\n ");
}

function nextDay(day: string): string {
    const [y, m, d] = day.split("-").map(Number) as [number, number, number];
    return new Date(Date.UTC(y, m - 1, d + 1)).toISOString().slice(0, 10).replace(/-/g, "");
}

/**
 * Opens one event prefilled in Google Calendar; on Android it goes to the Calendar app,
 * which cannot import an .ics file.
 */
export function googleCalendarUrl(event: CalendarEvent): string {
    const dates = "day" in event
        ? `${event.day.replace(/-/g, "")}/${nextDay(event.day)}`
        : `${utcStamp(event.start)}/${utcStamp(event.end)}`;
    const params = new URLSearchParams({ action: "TEMPLATE", text: event.summary, dates });
    if (event.description) params.set("details", event.description);
    if (event.location) params.set("location", event.location);
    return `https://calendar.google.com/calendar/render?${params}`;
}

export function buildCalendar(events: CalendarEvent[], now = new Date()): string {
    const lines = ["BEGIN:VCALENDAR", "VERSION:2.0", "PRODID:-//BK Praha//Vysledky//CS", "CALSCALE:GREGORIAN", "METHOD:PUBLISH"];
    for (const event of events) {
        lines.push("BEGIN:VEVENT", `UID:${event.uid}`, `DTSTAMP:${utcStamp(now)}`);
        if ("day" in event) {
            lines.push(`DTSTART;VALUE=DATE:${event.day.replace(/-/g, "")}`, `DTEND;VALUE=DATE:${nextDay(event.day)}`);
        } else {
            lines.push(`DTSTART:${utcStamp(event.start)}`, `DTEND:${utcStamp(event.end)}`);
        }
        lines.push(`SUMMARY:${escapeText(event.summary)}`);
        if (event.description) lines.push(`DESCRIPTION:${escapeText(event.description)}`);
        if (event.location) lines.push(`LOCATION:${escapeText(event.location)}`);
        if (event.url) lines.push(`URL:${event.url}`);
        lines.push("END:VEVENT");
    }
    lines.push("END:VCALENDAR");
    return lines.map(fold).join("\r\n") + "\r\n";
}
