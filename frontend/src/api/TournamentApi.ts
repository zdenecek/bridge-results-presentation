import TournamentEntry from "@/model/TournamentEntry";
import { Tournament } from "../model/Tournament";

import axios from "axios";

export default class TournamentApi {

    private static url: string = import.meta.env.VITE_API_URL;

    public static getTournament(slug: string): Promise<TournamentEntry> {
        return axios.get(this.url + "tournament/" + slug).then((response) => {
            return new TournamentEntry(response.data);
        });
    }

    public static getTournaments(): Promise<TournamentEntry[]> {
        return axios.get(this.url + "tournaments" ).then((response) => {
            return response.data.map((tournament: any) => new TournamentEntry(tournament));
        });
    }

    public static createTournament(name: string, slug: string, key: string): Promise<number> {
        return axios.post(this.url + "tournament", {name, slug}, { headers: { "Apikey": key }}).then((response) => {
            return response.data.id;
        });
    
    }

    public static deleteTournament(id: number, key: string): Promise<void> {
        return axios.delete(this.url + "tournament/" + id, { headers: { "Apikey": key }}).then((response) => {
            return;
        });
    }

    public static updateTournament(id: number, name: string, slug: string, data: any, key: string): Promise<void> {
        return axios.put(this.url + "tournament/" + id, {name, slug, data}, { headers: { "Apikey": key }}).then((response) => {
            return;
        });
    }
}
