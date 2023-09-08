import { TableNumber } from "./modelTypes";

export type ResultOverwriteVP = {
    type: "vp";
    vp_ns: number;
    vp_ew: number;
};

export type ResultOverwriteIMP = {
    type: "imp";
    imp_diff_ns?: number;
    imp_diff_ew?: number;
};

export type ResultOverwritePlayer = {
    type: "player";
    ns?: string;
    ew?: string;
};

export type ResultOverwriteIgnore = {
    type: "ignore";
};

export type ResultOverwritePostponed = {
    type: "postponed";
    imp_ns: number;
    imp_ew: number;
    date?: string;
    externalUrl?: string;
};

export type OverwrittenResult = {
    table: TableNumber;
} & (
    | ResultOverwriteVP
    | ResultOverwriteIMP
    | ResultOverwritePlayer
    | ResultOverwriteIgnore
    | ResultOverwritePostponed
);
