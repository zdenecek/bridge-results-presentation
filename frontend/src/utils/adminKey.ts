import axios from "axios";
import { ref } from "vue";

const STORAGE_KEY = "adminKey";

function read(storage: () => Storage): string {
    try {
        return storage().getItem(STORAGE_KEY) ?? "";
    } catch {
        return "";
    }
}

function write(storage: () => Storage, value: string | null) {
    try {
        if (value === null) storage().removeItem(STORAGE_KEY);
        else storage().setItem(STORAGE_KEY, value);
    } catch {
        // Private mode or blocked storage: the key then lasts until the page is reloaded.
    }
}

/** The shared admin password, entered once and sent with every write request. */
export const adminKey = ref(read(() => sessionStorage) || read(() => localStorage));

export async function unlock(key: string, remember: boolean): Promise<void> {
    await axios.get(import.meta.env.VITE_API_URL + "auth", { headers: { "Apikey": key } });
    adminKey.value = key;
    write(() => sessionStorage, key);
    write(() => localStorage, remember ? key : null);
}

export function lock() {
    adminKey.value = "";
    write(() => sessionStorage, null);
    write(() => localStorage, null);
}

// A changed password makes the stored key useless, so ask for it again instead of failing every save.
axios.interceptors.response.use(undefined, (error) => {
    if (error?.response?.status === 401 && error.config?.headers?.["Apikey"]) lock();
    return Promise.reject(error);
});
