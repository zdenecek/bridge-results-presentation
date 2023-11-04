import { TableNumber } from "./modelTypes";

export type TableOverwrite = {
    table: TableNumber;
};

export type ResultOverwriteVP = {
    type: "vp";
    vp_ns: number;
    vp_ew: number;
} & TableOverwrite;

export type ResultOverwriteIMP = {
    type: "imp";
    imp_diff_ns?: number;
    imp_diff_ew?: number;
} & TableOverwrite;

export type ResultOverwritePlayer = {
    type: "player";
    ns?: string;
    ew?: string;
} & TableOverwrite;

export type ResultOverwriteIgnore = {
    type: "ignore";
} & TableOverwrite;

export type ResultOverwritePostponed = {
    type: "postponed";
    imp_ns?: number;
    imp_ew?: number;
    date?: string;
    externalUrl?: string;
} & TableOverwrite;

export type OverwrittenResult = (
    ResultOverwriteVP
    | ResultOverwriteIMP
    | ResultOverwritePlayer
    | ResultOverwriteIgnore
    | ResultOverwritePostponed
);


export const OverwriteTypes = ["vp",
    "imp",
    "player",
    "ignore",
    "postponed"];