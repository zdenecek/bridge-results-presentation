
export type BoardResult = AdjustedBoardResult | PlayedBoardResult | NotPlayedBoardResult;


type BoardResultPart =  {
    deal: number;
    ns: number;
    ew: number;
    status: string;
}


export type NotPlayedBoardResult = BoardResultPart & { status: "not-played" };

export type CompleteBoardResult = PlayedBoardResult | AdjustedBoardResult;

export type PlayedBoardResult = BoardResultPart & {
    status: "played"
    rotated?: boolean;
    contract: string;
    declarer: string;
    result: string;
    points: number;
    res_ns: number;
    res_ew: number;
};

export type AdjustedBoardResult  = BoardResultPart & {
    status: "adjusted";
    text?: string;
    res_ns: number;
    res_ew: number;
}
