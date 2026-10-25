import axios from "axios";

export interface BavlnkaContribution {
    id: number;
    date: string;
    rank: string;
    pairs: number;
    weight: number;
    points: number;
}

export interface BavlnkaPlayer {
    rank: number;
    name: string;
    points: number;
    tournaments: BavlnkaContribution[];
}

export interface BavlnkaTournament {
    id: number;
    date: string;
    url: string;
    pairs: number;
    weight: number;
}

export interface BavlnkaRanking {
    updated: string | null;
    rules: {
        window: number;
        blockSize: number;
        blockDecay: number;
        participationPoints: number;
        pointsPerPairBeaten: number;
    };
    tournaments: BavlnkaTournament[];
    ranking: BavlnkaPlayer[];
}

export interface BavlnkaStoredTournament {
    id: number;
    source_url: string;
    results_url: string | null;
    listed_date: string | null;
    date: string | null;
    format: string | null;
    title: string | null;
    pairs: number | null;
    status: "pending" | "ok" | "error" | "duplicate" | "ignored";
    error: string | null;
    attempts: number;
    fetched_at: string | null;
    players: number;
}

export interface BavlnkaAdminPlayer {
    name: string;
    spellings: string[];
    cbsIds: number[];
    count: number;
    last: string;
}

export interface BavlnkaAdminData {
    tournaments: BavlnkaStoredTournament[];
    aliases: Record<string, string>;
    players: BavlnkaAdminPlayer[];
}

export interface BavlnkaUpdateReport {
    discovered: { date: string | null; url: string }[];
    processed: { id: number; date: string; status: string; pairs: number }[];
    errors: { source?: string; id?: number; url?: string; error: string }[];
    remaining: number;
}

export default class BavlnkaApi {

    private static url: string = import.meta.env.VITE_API_URL + "bavlnka/";

    public static getRanking(): Promise<BavlnkaRanking> {
        return axios.get(this.url + "ranking").then((response) => response.data);
    }

    public static getAdminData(key: string): Promise<BavlnkaAdminData> {
        return axios.get(this.url + "admin", { headers: { "Apikey": key } }).then((response) => response.data);
    }

    public static update(key: string): Promise<BavlnkaUpdateReport> {
        return axios.post(this.url + "update", {}, { headers: { "Apikey": key }, timeout: 120000 })
            .then((response) => response.data);
    }

    public static addTournament(url: string, date: string, key: string): Promise<void> {
        return axios.post(this.url + "tournaments", { url, date }, { headers: { "Apikey": key } }).then(() => undefined);
    }

    public static setTournamentStatus(id: number, status: "pending" | "ignored", key: string): Promise<void> {
        return axios.put(this.url + "tournaments/" + id, { status }, { headers: { "Apikey": key } }).then(() => undefined);
    }

    public static setAliases(aliases: Record<string, string>, key: string): Promise<void> {
        return axios.put(this.url + "aliases", { aliases }, { headers: { "Apikey": key } }).then(() => undefined);
    }
}
