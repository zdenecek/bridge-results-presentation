import axios from "axios";

export interface Promo {
    title: string;
    titleEn: string | null;
    text: string;
    textEn: string | null;
    /** Short facts separated by semicolons, shown as chips. */
    facts: string | null;
    factsEn: string | null;
    url: string | null;
    linkLabel: string | null;
    linkLabelEn: string | null;
    /** Prague time, `2026-11-28T18:00`. */
    closes: string | null;
    /** Slug of the tournament whose pages show the promo at its largest. */
    tournament: string | null;
}

export interface PromoSettings extends Promo {
    enabled: boolean;
}

export default class PromoApi {
    private static url: string = import.meta.env.VITE_API_URL + "promo";

    static get(): Promise<Promo | null> {
        return axios.get(this.url).then((response) => response.data.promo ?? null);
    }

    static getSettings(key: string): Promise<PromoSettings> {
        return axios.get(this.url + "/admin", { headers: { "Apikey": key } }).then((response) => response.data);
    }

    static save(key: string, promo: PromoSettings): Promise<PromoSettings> {
        return axios.put(this.url, promo, { headers: { "Apikey": key } }).then((response) => response.data);
    }
}
