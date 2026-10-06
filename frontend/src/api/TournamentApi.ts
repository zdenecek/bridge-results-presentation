import TournamentEntry from "@/model/TournamentEntry";

import axios from "axios";

// The API lets browsers reuse a tournament for 9 minutes, so after a save this
// browser asks for a fresh copy a bit longer than that.
const SAVED_AT_KEY = "tournamentSavedAt";
const FRESH_AFTER_SAVE_MS = 10 * 60 * 1000;

function markSaved() {
    try {
        localStorage.setItem(SAVED_AT_KEY, Date.now().toString());
    } catch {
        // Storage disabled: the browser cache expires on its own.
    }
}

function savedRecently(): boolean {
    try {
        return Date.now() - Number(localStorage.getItem(SAVED_AT_KEY) ?? 0) < FRESH_AFTER_SAVE_MS;
    } catch {
        return false;
    }
}

export default class TournamentApi {

    private static url: string = import.meta.env.VITE_API_URL;

    public static getTournament(slug: string, fresh = false): Promise<TournamentEntry> {
        const cacheBuster = fresh || savedRecently() ? `?cb=${Date.now()}` : "";
        return axios.get(this.url + "tournament/" + slug + cacheBuster).then((response) => {
            return new TournamentEntry(response.data);
        });
    }

    public static getTournaments(): Promise<TournamentEntry[]> {
        return axios.get(this.url + "tournaments" ).then((response) => {
            return response.data.map((tournament: any) => new TournamentEntry(tournament));
        });
    }

    public static createTournament(name: string, slug: string, data: any, key: string): Promise<number> {
        return axios.post(this.url + "tournament", {name, slug, data}, { headers: { "Apikey": key }}).then((response) => {
            markSaved();
            return response.data.id;
        });
    
    }

    public static deleteTournament(id: number, key: string): Promise<void> {
        return axios.delete(this.url + "tournament/" + id, { headers: { "Apikey": key }}).then(() => markSaved());
    }

    public static transcribeSlip(image: string, mimeType: string, key: string): Promise<{ text: string, model: string }> {
        return axios.post(this.url + "transcribe-slip", { image, mimeType }, { headers: { "Apikey": key }, timeout: 180000 })
            .then((response) => response.data);
    }

    public static updateTournament(id: number, name: string, slug: string, data: any, key: string): Promise<void> {
        return axios.put(this.url + "tournament/" + id, {name, slug, data}, { headers: { "Apikey": key }}).then(() => markSaved());
    }
}
