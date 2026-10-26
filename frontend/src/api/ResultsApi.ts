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
}

/** "2026-27" as the club writes it. */
export function seasonLabel(season: string): string {
    return season.replace("-", "/");
}

export function currentSeason(today = new Date()): string {
    const year = today.getMonth() >= 8 ? today.getFullYear() : today.getFullYear() - 1;
    return `${year}-${String((year + 1) % 100).padStart(2, "0")}`;
}
