import { Rank } from "./Rank";
import { calculateVP } from "./VP";
import { PairNumber, RoundNumber } from "./modelTypes";

export interface MatchResult {
    round: number;
    ns: number;
    ew: number;
    ns_override?: string;
    ew_override?: string;
    status: string;
}

export interface ScoredMatchResult extends MatchResult {
    vp_ns: number;
    vp_ew: number;
}

export class PlayedMatchResult implements ScoredMatchResult {
    imp_ns: number;
    imp_ew: number;
    vp_ns: number;
    vp_ew: number;
    round: number;
    ns: number;
    ew: number;
    ns_override?: string;
    ew_override?: string;
    status: string = "played";

    constructor(
        data: {
            round: number;
            ns: number;
            ew: number;
            imp_ns: number;
            imp_ew: number;
            status?: string;
        },
        boardCount = 28
    ) {
        this.round = data.round;
        this.ns = data.ns;
        this.ew = data.ew;
        this.imp_ns = data.imp_ns;
        this.imp_ew = data.imp_ew;
        const vp = calculateVP(data.imp_ns - data.imp_ew, boardCount);
        this.vp_ns = vp.ns;
        this.vp_ew = vp.ew;

        if (data.status) this.status = data.status;
    }
}

export type PostponedResult = { status: "postponed" } & PlayedMatchResult;
export type ScratchedResult = { status: "scratched" } & ScoredMatchResult;
export type NotplayedResult = { status: "not-played" } & MatchResult;
export type PlayedResult = { status: "played" } & PlayedMatchResult;

export type TableRoundResult = { table: number } & (
    | PostponedResult
    | ScratchedResult
    | NotplayedResult
    | PlayedResult
);

export interface PairSumResult {
    pair: PairNumber;
    vp: number;
    rank: Rank;
    matchResults: PairTableRoundResult[];
}
export class PairSumResult {
    pair!: PairNumber;
    vp!: number;
    rank!: Rank;
    matchResults: PairTableRoundResult[];

    constructor(data: {
        pair: PairNumber,
        vp: number,
        rank: Rank,
        matchResults?: PairTableRoundResult[],
    }) {
        this.pair = data.pair;
        this.vp = data.vp;
        this.rank = data.rank;
        this.matchResults = data.matchResults ?? [];
    }

    get average(): number | undefined {
        if(this.matchCount === 0) return undefined;
        return this.vp / this.matchCount;
    }

    get averageAsNumber(): number {
        return this.average ?? 0;
    }

    get matchCount(): number {
        return this.matchResults.filter((r) => r.status !== "not-played")
            .length;
    }

    static Default(player?: number | undefined, groupSize?: number | undefined): PairSumResult {
        return new PairSumResult({
            pair: player ?? 0,
            vp: 0,
            rank: Rank.default(groupSize ?? 10),
        });
    }
}

export class MatchResults {
    static getImpsNS(result?: TableRoundResult): number | undefined {
        if (!result) return undefined;
        if (result.status === "not-played") return undefined;
        if (result.status === "scratched") return undefined;

        return result.imp_ns;
    }

    static getImpsEW(result?: TableRoundResult): number | undefined {
        if (!result) return undefined;
        if (result.status === "not-played") return undefined;
        if (result.status === "scratched") return undefined;

        return result.imp_ew;
    }

    static getVpsNS(result?: TableRoundResult): number | undefined {
        if (!result) return undefined;
        if (result.status === "not-played") return undefined;

        return result.vp_ns;
    }

    static getVpsEW(result?: TableRoundResult): number | undefined {
        if (!result) return undefined;
        if (result.status === "not-played") return undefined;

        return result.vp_ew;
    }
}

export class PairTableRoundResult {
    constructor(
        public tableResult: TableRoundResult,
        public pair: PairNumber
    ) {}

    get imps(): number | undefined {
        if (this.tableResult.status === "not-played") return undefined;
        if (this.tableResult.status === "scratched") return undefined;

        return this.ns ? this.tableResult.imp_ns : this.tableResult.imp_ew;
    }

    get vps(): number | undefined {
        if (this.tableResult.status === "not-played") return undefined;
        return this.ns ? this.tableResult.vp_ns : this.tableResult.vp_ew;
    }

    get ops(): PairNumber {
        return this.ns ? this.tableResult.ew : this.tableResult.ns;
    }

    get ns(): boolean {
        return this.tableResult.ns === this.pair;
    }

    get round(): RoundNumber {
        return this.tableResult.round;
    }

    get oppResult(): PairTableRoundResult {
        return new PairTableRoundResult(this.tableResult, this.ops);
    }

    get status(): string {
        return this.tableResult.status;
    }
}
