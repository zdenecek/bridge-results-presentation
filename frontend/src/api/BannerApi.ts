import axios from "axios";

export interface Banner {
    title: string;
    text: string;
    url: string | null;
    linkLabel: string | null;
    until: string | null;
    titleEn: string | null;
    textEn: string | null;
    linkLabelEn: string | null;
}

export interface BannerSettings extends Banner {
    enabled: boolean;
}

export default class BannerApi {
    private static url: string = import.meta.env.VITE_API_URL + "banner";

    static get(): Promise<Banner | null> {
        return axios.get(this.url).then((response) => response.data.banner ?? null);
    }

    static getSettings(key: string): Promise<BannerSettings> {
        return axios.get(this.url + "/admin", { headers: { "Apikey": key } }).then((response) => response.data);
    }

    static save(key: string, banner: BannerSettings): Promise<BannerSettings> {
        return axios.put(this.url, banner, { headers: { "Apikey": key } }).then((response) => response.data);
    }
}
