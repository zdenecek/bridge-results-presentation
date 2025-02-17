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
    boards?: Record<BoardNumberKey, Board>;
    boardResults?: BoardResult[];
    overwrites?: Overwrite[];
    averages?: Record<BoardNumberKey, number>;
}

/**
 * Represents a round of a match along with its results.
 * Main resposibility is to calculate the match results and store them to matchResultsByTable upon creation.
 */
export class Round {
    readonly date?: Date;
    readonly boards?: Map<BoardNumber, Board>;
    readonly boardResults?: BoardResult[];

    private matchResultsByTable: Map<TableNumber, TableRoundResult>;
    private matchResultsByPair: Map<PairNumber, TableRoundResult>;
    private boardAverages?: Map<BoardNumber, number>;

    private postponedTables: TableNumber[] = [];

    constructor(data: RoundData, public readonly number: number, public readonly rotation: RoundRotation) {
        if(data.date) 
        this.date = new Date(data.date);
        this.boardResults = data.boardResults;

        this.boards = new Map<BoardNumber, Board>();
        if(data.boards !== undefined) {
            Object.keys(data.boards).forEach(num => {

                if(data.boards![num].deal.length !== 4) 
                {
                    console.warn(`Board ${num} in round ${this.number} has ${data.boards![num].deal.length} cards`);
                    return;
                }
                this.boards?.set(Number.parseInt(num), data.boards![num]);
            })
        }

        const results = calculateResults(this, data);

        const overwrites = data.overwrites ?? [];

        overwrites.filter((o) => o.type === "postponed").forEach((o) => {
            this.postponedTables.push(o.table);
        });

        this.matchResultsByPair = new Map<PairNumber, TableRoundResult>();
        
        this.matchResultsByTable = this.applyOverwrites(
            overwrites,
            results
        );
        
        this.matchResultsByTable.forEach((result, tableNumber) => {
            this.matchResultsByPair!.set(result.ns, result);
            this.matchResultsByPair!.set(result.ew, result);
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

    public get wasPlayed(): boolean {
        return (this.boardResults?.length ?? 0) > 0;
    }

    public getBoardAverage(boardNumber: number) : number | undefined {
        return this.boardAverages?.get(boardNumber);
    }
    
    public getMatchResults(): TableRoundResult[] {
        if(this.matchResultsByTable === undefined) return [];
        return Array.from(this.matchResultsByTable.values());
    }

    public getPairResult(pairNumber: PairNumber): PairTableRoundResult | undefined {
        const result = this.matchResultsByPair?.get(pairNumber);
        if(result) return new PairTableRoundResult(result, pairNumber);
    }

    public getTableResult(
        tableNumber: TableNumber
    ): TableRoundResult | undefined {
        return this.matchResultsByTable?.get(tableNumber);
    }

    public isTablePostponed(tableNumber: TableNumber): boolean {
        return this.postponedTables.includes(tableNumber);
    }

    private applyOverwrites(
        overwrites: Overwrite[],
        resultsByTable: Map<TableNumber, TableRoundResult>
    ): Map<TableNumber, TableRoundResult> {
        overwrites
            .filter((o) => o.type === "ignore")
            .forEach((o) => {

                let result = resultsByTable.get(o.table);
                if (!result) {
                    console.warn(`Table ${o.table} not found in round ${this.number}`);
                    return;
                }
                result = {
                    ns:  result.ns,
                    ew: result.ew,
                    status: "not-played",
                    round: result.round,
                    table: result.table,
                };
                resultsByTable.set(o.table, result);
            });

        // Postponed
        (overwrites
            .filter((o) => o.type === "postponed") as ResultOverwritePostponed[])
            .filter( o => o.imp_ew !== undefined && o.imp_ns !== undefined)
            .forEach((o) => {
                if (resultsByTable.get(o.table)?.status === "played")
                    console.warn(`Postponed table ${o.table} already played, round ${this.number}`);
                if (resultsByTable.get(o.table)?.status === "postponed")
                    console.warn(`Postponed table ${o.table} duplicate entry, round ${this.number}`);

                const result =
                    resultsByTable.get(o.table) ??
                    throwError(`Postponed table not found: ${o.table}`);
                const overwrite = o as ResultOverwritePostponed;
                const matchResult = {
                    ...new PlayedMatchResult({
                        round: this.number,
                        ns: result.ns,
                        ew: result.ew,
                        imp_ns: overwrite.imp_ns!,
                        imp_ew: overwrite.imp_ew!,
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
                const new_result = { ...result };

                if(overwrite.vp_ns !== undefined) (new_result as any).vp_ns = overwrite.vp_ns;
                if(overwrite.vp_ew !== undefined) (new_result as any).vp_ew = overwrite.vp_ew;
                if(overwrite.vp_ns !== undefined && overwrite.vp_ew !== undefined) new_result.status = "scratched";

                resultsByTable.set(o.table, new_result);
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
