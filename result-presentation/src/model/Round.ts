import { throwError } from "@/utils/error";
import { Board, BoardNumber, BoardNumberKey } from "./Board";
import { BoardResult } from "./BoardResult";
import { PairTableRoundResult, PlayedMatchResult, PostponedResult, ScratchedResult, TableRoundResult } from "./MatchResult";
import {
    OverwrittenResult as Overwrite,
    ResultOverwriteIMP,
    ResultOverwritePlayer,
    ResultOverwritePostponed,
    ResultOverwriteVP,
} from "./Overwrites";
import { calculateResults } from "./ResultsCalculation";
import { Pair, PairNumber, RoundRotation, TableNumber } from "./modelTypes";
import { calculateVP } from "./VP";

export interface RoundData {
    date?: string | Date;
    number: number;
    boards?: Record<BoardNumberKey, Board>;
    boardResults: BoardResult[];
    overwrites: Overwrite[];
    averages?: Record<BoardNumberKey, number>;
}

/**
 * Represents a round of a match along with its results.
 * Main resposibility is to calculate the match results and store them to matchResultsByTable upon creation.
 */
export class Round {
    readonly number: number;
    readonly date?: string;
    readonly boards?: Record<BoardNumberKey, Board>;
    readonly boardResults!: BoardResult[];

    private matchResultsByTable: Map<TableNumber, TableRoundResult>;
    private matchResultsByPair: Map<PairNumber, TableRoundResult>;
    private boardAverages?: Map<BoardNumber, number>;

    constructor(data: RoundData, public rotation: RoundRotation) {
        this.date = data.date?.toString();
        this.number = data.number;
        this.boards = data.boards;
        this.boardResults = data.boardResults;

        const results = calculateResults(this, data);

        this.matchResultsByTable = this.applyOverwrites(
            data.overwrites,
            results
        );

        this.matchResultsByPair = new Map<PairNumber, TableRoundResult>();
        this.matchResultsByTable.forEach((result, tableNumber) => {
            this.matchResultsByPair.set(result.ns, result);
            this.matchResultsByPair.set(result.ew, result);
        });

        if(data.averages !== undefined) {
            this.boardAverages = new Map<BoardNumber, number>();
            Object.keys(data.averages).forEach(num => {
                this.boardAverages?.set(Number.parseInt(num), data.averages![num]);
            })
        }
    }

    public get hasAverages() {
        return this.boardAverages;
    }

    public getBoardAverage(boardNumber: number) : number | undefined {
        return this.boardAverages?.get(boardNumber);
    }
    
    public getMatchResults(): TableRoundResult[] {
        return Array.from(this.matchResultsByTable.values());
    }

    public getPairResult(pairNumber: PairNumber): PairTableRoundResult | undefined {
        const result = this.matchResultsByPair.get(pairNumber);
        if(result) return new PairTableRoundResult(result, pairNumber);
    }

    public getTableResult(
        tableNumber: TableNumber
    ): TableRoundResult | undefined {
        return this.matchResultsByTable.get(tableNumber);
    }

    private applyOverwrites(
        overwrites: Overwrite[],
        resultsByTable: Map<TableNumber, TableRoundResult>
    ): Map<TableNumber, TableRoundResult> {
        overwrites
            .filter((o) => o.type === "ignore")
            .forEach((o) => {
                const success = resultsByTable.delete(o.table);
                if (!success) console.warn(`Table ${o.table} not found`);
            });

        // Postponed
        overwrites
            .filter((o) => o.type === "postponed")
            .forEach((o) => {
                if (resultsByTable.get(o.table)?.status === "played")
                    console.warn(`Postponed table ${o.table} already played`);
                if (resultsByTable.get(o.table)?.status === "postponed")
                    console.warn(`Postponed table ${o.table} duplicate entry`);

                const result =
                    resultsByTable.get(o.table) ??
                    throwError(`Postponed table not found: ${o.table}`);
                const overwrite = o as ResultOverwritePostponed;
                const matchResult = {
                    ...new PlayedMatchResult({
                        round: this.number,
                        ns: result.ns,
                        ew: result.ew,
                        imp_ns: overwrite.imp_ns,
                        imp_ew: overwrite.imp_ew,
                    }),
                    status: "postponed",
                    table: o.table,
                } as TableRoundResult;

                resultsByTable.set(o.table, matchResult);
            });

        overwrites
            .filter((o) => o.type === "imp")
            .forEach((o) => {
                const result = resultsByTable.get(o.table);
                if (!result) return;
                if (
                    result.status === "not-played" ||
                    result.status === "scratched"
                )
                    return;

                const overwrite = o as ResultOverwriteIMP;
                if (overwrite.imp_diff_ns)
                    result.imp_ns += overwrite.imp_diff_ns;
                if (overwrite.imp_diff_ew)
                    result.imp_ew += overwrite.imp_diff_ew;

                const vps = calculateVP(result.imp_ns - result.imp_ew);
                result.vp_ns = vps.ns;
                result.vp_ew = vps.ew;
            });

        overwrites
            .filter((o) => o.type === "vp")
            .forEach((o) => {
                const result = resultsByTable.get(o.table);
                if (!result) return;

                const overwrite = o as ResultOverwriteVP;
                const newResult = {
                    ...result,
                    vp_ns: overwrite.vp_ns,
                    vp_ew: overwrite.vp_ew,
                    status: "scratched",
                } as TableRoundResult;
                resultsByTable.set(o.table, newResult);
            });

        overwrites
            .filter((o) => o.type === "player")
            .forEach((o) => {
                const result = resultsByTable.get(o.table);
                if (!result) return;
                if (
                    result.status === "not-played" ||
                    result.status === "scratched"
                )
                    return;

                const overwrite = o as ResultOverwritePlayer;
                if (overwrite.ns) result.ns_override = overwrite.ns;
                if (overwrite.ew) result.ew_override = overwrite.ew;
            });

        return resultsByTable;
    }

}
