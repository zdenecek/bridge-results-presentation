import { reactive, watch } from "vue";
import { useRoute } from "vue-router";
import { Pair, PairNumber } from "@/model/modelTypes";
import { Tournament } from "@/model/Tournament";

const STORAGE_KEY = "settings";

export type ResultsOrder = "score" | "table";

export interface Settings {
    /** The pair followed in each tournament, by the tournament's address. */
    followPairs: Record<string, PairNumber>;
    /** ČBS number of the player followed in every skupinovka. */
    followPlayer: string;
    showHcp: boolean;
    resultsOrder: ResultsOrder;
}

function defaults(): Settings {
    return { followPairs: {}, followPlayer: "", showHcp: false, resultsOrder: "score" };
}

function read(): Settings {
    try {
        const stored = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? "{}");
        return { ...defaults(), ...stored };
    } catch {
        return defaults();
    }
}

export const settings = reactive<Settings>(read());

watch(settings, (value) => {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(value));
    } catch {
        // Blocked storage: the settings last until the page is reloaded.
    }
}, { deep: true });

export function followedPair(tournament: string): PairNumber | undefined {
    return settings.followPairs[tournament];
}

export function setFollowedPair(tournament: string, pair: PairNumber | undefined) {
    if (pair === undefined) delete settings.followPairs[tournament];
    else settings.followPairs[tournament] = pair;
}

export function resetFollowing() {
    settings.followPairs = {};
    settings.followPlayer = "";
}

/** A pair is followed when it is the one chosen in this tournament or the followed player plays in it. */
export function isFollowed(tournament: string, pair: Pair | undefined): boolean {
    if (!pair) return false;
    if (settings.followPairs[tournament] === pair.id) return true;
    return !!settings.followPlayer && pair.players.some((p) => p.id === settings.followPlayer);
}

/** For the tables of the tournament on the current page: whether a pair number is followed. */
export function useFollowed(tournament: () => Tournament | undefined) {
    const route = useRoute();
    return (pair: PairNumber | undefined) =>
        pair !== undefined && isFollowed(String(route.params.tournament), tournament()?.getPair(pair));
}
