import axios from "axios";

export type PreborResultKind = "registered" | "substitute" | "assigned" | "own" | "conflict" | "unregistered";

export interface PreborRoundResult {
    round: number;
    number: number;
    score: number;
    rank: string;
    lineup: string;
    kind: PreborResultKind;
    counted: boolean;
}

export interface PreborStanding {
    rank: number;
    number: number | null;
    name: string;
    players: string[];
    total: number;
    played: number;
    results: PreborRoundResult[];
}

export interface PreborRound {
    id: number;
    number: number;
    date: string;
    url: string | null;
    pairs: number;
}

export interface PreborCompetition {
    slug: string;
    season: string;
    title: string;
    title_en?: string | null;
    unit: string;
    best: number;
    rounds: number;
    registration_csv: string | null;
}

export interface PreborCompetitionSummary extends Omit<PreborCompetition, "registration_csv"> {
    played: number;
}

export interface PreborRanking {
    slug: string;
    season: string;
    title: string;
    title_en?: string | null;
    unit: string;
    best: number;
    totalRounds: number;
    updated: string | null;
    rounds: PreborRound[];
    ranking: PreborStanding[];
}

export interface PreborStoredRound {
    id: number;
    date: string | null;
    url: string | null;
    source: "tc" | "manual";
    title: string | null;
    pairs: number | null;
    status: "pending" | "ok" | "error" | "ignored";
    error: string | null;
    fetched_at: string | null;
}

export interface PreborEntry {
    number: number;
    title: string;
    players: string[];
}

export interface PreborReviewRow {
    round: number;
    date: string | null;
    lineup: string;
    players: string[];
    playersKey: string;
    kind: PreborResultKind;
    entry: number;
}

export interface PreborAdminCompetition extends PreborCompetition {
    id: number;
    played: number;
    roundList: PreborStoredRound[];
    entries: PreborEntry[];
    review: PreborReviewRow[];
}

export interface PreborAdminData {
    competitions: PreborAdminCompetition[];
    cronKey: string;
}

export interface PreborNewRound {
    url?: string;
    date?: string;
    results?: string;
}

export default class PreborApi {

    private static url: string = import.meta.env.VITE_API_URL + "prebor/";

    public static getCompetitions(): Promise<PreborCompetitionSummary[]> {
        return axios.get(this.url.replace(/\/$/, "")).then((response) => response.data);
    }

    public static getRanking(slug: string): Promise<PreborRanking> {
        return axios.get(this.url + slug + "/ranking").then((response) => response.data);
    }

    public static getAdminData(key: string): Promise<PreborAdminData> {
        return axios.get(this.url + "admin", { headers: { "Apikey": key } }).then((response) => response.data);
    }

    public static cronUrl(cronKey: string): string {
        return new URL(this.url + "update?key=" + encodeURIComponent(cronKey), window.location.href).href;
    }

    public static csvUrl(slug: string): string {
        return new URL(this.url + slug + "/ranking.csv", window.location.href).href;
    }

    public static saveCompetition(competition: PreborCompetition, id: number | null, key: string): Promise<void> {
        const request = id === null
            ? axios.post(this.url + "competitions", competition, { headers: { "Apikey": key } })
            : axios.put(this.url + "competitions/" + id, competition, { headers: { "Apikey": key } });
        return request.then(() => undefined);
    }

    public static update(key: string): Promise<{ processed: unknown[]; errors: { url: string; error: string }[] }> {
        return axios.post(this.url + "update", {}, { headers: { "Apikey": key }, timeout: 120000 })
            .then((response) => response.data);
    }

    public static addRound(slug: string, round: PreborNewRound, key: string): Promise<void> {
        return axios.post(this.url + slug + "/rounds", round, { headers: { "Apikey": key }, timeout: 60000 })
            .then(() => undefined);
    }

    public static roundAction(id: number, action: "refetch" | "ignore" | "restore" | "delete", key: string): Promise<void> {
        return axios.put(this.url + "rounds/" + id, { action }, { headers: { "Apikey": key }, timeout: 60000 })
            .then(() => undefined);
    }

    public static importEntries(slug: string, key: string): Promise<number> {
        return axios.post(this.url + slug + "/entries/import", {}, { headers: { "Apikey": key } })
            .then((response) => response.data.entries);
    }

    public static setAssignment(round: number, playersKey: string, entry: number | null, key: string): Promise<void> {
        return axios.put(this.url + "rounds/" + round + "/assignments", { playersKey, entry }, { headers: { "Apikey": key } })
            .then(() => undefined);
    }
}
