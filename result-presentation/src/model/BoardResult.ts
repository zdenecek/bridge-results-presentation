
export type BoardResult = CompleteBoardResult | IncompleteBoardResult;

export interface IncompleteBoardResult {
    deal: number;
    ns: number;
    ew: number;
    status: "not-played" | string;
}

export interface CompleteBoardResult {
    status: "played" | string; // todo fix
    deal: number;
    rotated?: boolean;
    ns: number;
    ew: number;
    contract: string;
    declarer: string;
    result: string;
    points: number;
    res_ns: number;
    res_ew: number;
}
