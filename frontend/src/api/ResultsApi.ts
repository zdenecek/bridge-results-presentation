import axios from "axios";

export interface ResultsPlayer {
    first: string;
    last: string;
}

export type ResultsWinner =
    | { kind: "pair"; entries: { players: ResultsPlayer[] }[] }
    | { kind: "team"; entries: { name: string }[] }
    | { kind: "text"; text: string };

export interface ResultsEvent {
    id: number;
    date: string | null;
    title: string | null;
    url: string | null;
    ongoing?: boolean;
    winner: ResultsWinner | null;
    status?: "pending" | "ok" | "error" | "link";
    error?: string | null;
    format?: string | null;
    winner_override?: string | null;
}

export interface ResultsPreborSummary {
    slug: string;
    title: string;
    played: number;
    rounds: number;
    lastRound: { number: number; date: string; url: string | null; winner: ResultsWinner } | null;
}

export interface ResultsSection {
    id: number;
    parent: number | null;
    title: string;
    kind: "group" | "events" | "tournament" | "prebor" | "bavlnka";
    ref: string | null;
    note: string | null;
    children: ResultsSection[];
    events?: ResultsEvent[];
    tournament?: { id: number; name: string; slug: string } | null;
    prebor?: ResultsPreborSummary | null;
    leaders?: { updated: string | null; players: (ResultsPlayer & { rank: number; points: number })[] } | null;
}

export interface ResultsPage {
    season: string;
    seasons: string[];
    sections: ResultsSection[];
}

export default class ResultsApi {

    private static url: string = import.meta.env.VITE_API_URL + "results/";

    public static getSeasons(): Promise<string[]> {
        return axios.get(this.url + "seasons").then((response) => response.data);
    }

    public static getPage(season: string): Promise<ResultsPage> {
        return axios.get(this.url + season).then((response) => response.data);
    }

    public static getAdminPage(season: string, key: string): Promise<ResultsPage> {
        return axios.get(this.url + season + "/admin", { headers: { "Apikey": key } }).then((response) => response.data);
    }

    /** Every change answers with the season's admin page, so the admin shows the stored state right away. */
    private static write(method: "post" | "put" | "delete", path: string, data: object, key: string): Promise<ResultsWriteResult> {
        return axios.request({ method, url: this.url + path, data, headers: { "Apikey": key }, timeout: 90000 })
            .then((response) => response.data);
    }

    public static saveSection(section: ResultsSectionInput, id: number | null, key: string) {
        return this.write(id === null ? "post" : "put", "sections" + (id === null ? "" : "/" + id), section, key);
    }

    public static deleteSection(id: number, key: string) {
        return this.write("delete", "sections/" + id, {}, key);
    }

    public static moveSection(id: number, direction: -1 | 1, key: string) {
        return this.write("post", "sections/" + id + "/move", { direction }, key);
    }

    public static addEvent(sectionId: number, event: ResultsEventInput, key: string) {
        return this.write("post", "sections/" + sectionId + "/events", event, key);
    }

    public static updateEvent(id: number, event: ResultsEventInput & { section_id?: number }, key: string) {
        return this.write("put", "events/" + id, event, key);
    }

    public static refetchEvent(id: number, key: string) {
        return this.write("post", "events/" + id + "/refetch", {}, key);
    }

    public static deleteEvent(id: number, key: string) {
        return this.write("delete", "events/" + id, {}, key);
    }

    public static copySeason(from: string, to: string, key: string) {
        return this.write("post", to + "/copy", { from }, key);
    }
}

export interface ResultsSectionInput {
    season: string;
    parent_id: number | null;
    title: string;
    kind: ResultsSection["kind"];
    ref: string | null;
    note: string | null;
}

export interface ResultsEventInput {
    url?: string;
    date: string | null;
    title: string | null;
    winner_override?: string | null;
    ongoing: boolean;
}

export interface ResultsWriteResult {
    result: unknown;
    page?: ResultsPage;
}

/** One line of text for a winner, as the admin lists it. */
export function winnerText(winner: ResultsWinner | null | undefined): string {
    if (!winner) return "";
    if (winner.kind === "text") return winner.text;
    if (winner.kind === "team") return winner.entries.map((e) => e.name).join(" a ");
    return winner.entries.map((e) => e.players.map((p) => `${p.first} ${p.last}`.trim()).join(" - ")).join(" a ");
}

/** "2026-27" as the club writes it. */
export function seasonLabel(season: string): string {
    return season.replace("-", "/");
}

export function currentSeason(today = new Date()): string {
    const year = today.getMonth() >= 8 ? today.getFullYear() : today.getFullYear() - 1;
    return `${year}-${String((year + 1) % 100).padStart(2, "0")}`;
}
