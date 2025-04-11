import { throwError } from "@/utils/error";
import { Board, BoardNumber, BoardNumberKey } from "./Board";
import { BoardResult } from "./BoardResult";
import { PairTableRoundResult, PlayedMatchResult, TableRoundResult } from "./MatchResult";
import {
    OverwrittenResult as Overwrite,
    ResultOverwriteIMP,
    ResultOverwritePlayer,
    ResultOverwritePostponed,
    ResultOverwriteVP,
    ResultAdjustmentOverwrite,
    ResultOverwriteIgnore
} from "./Overwrites";
import { calculateResults } from "./ResultsCalculation";
import {  PairNumber, RoundRotation, TableNumber } from "./modelTypes";
import { calculateVP } from "./VP";


type BoardResultOnlyTable = Omit<BoardResult, "ns" | "ew"> & { table: number };
type BoardResultsPartial = BoardResult | BoardResultOnlyTable;

export interface RoundData {
    date?: string | Date;
    boards?: Record<BoardNumberKey, Board>;
    boardResults?: BoardResultsPartial[];
    overwrites?: Overwrite[];
    averages?: Record<BoardNumberKey, number>;
}

export class ResultAdjustment {
    constructor(
        public readonly round: number,
        public readonly participant: PairNumber,
        public readonly vpAdjustment: number,
        public readonly reason: string,
    ) {}

    static fromOverwrite(overwrite: ResultAdjustmentOverwrite, round: number): ResultAdjustment {
        return new ResultAdjustment(round, overwrite.participant, overwrite.vpAdjustment, overwrite.reason);
    }
}

/**
 * Represents a round of a match along with its results.
 * Main resposibility is to calculate the match results and store them to matchResultsByTable upon creation.
 */
export class Round {
    readonly date?: Date;
    readonly boards?: Map<BoardNumber, Board>;
    readonly boardResults?: BoardResult[];
    readonly adjusts: ResultAdjustment[] = [];


    private matchResultsByTable: Map<TableNumber, TableRoundResult>;
    private matchResultsByPair: Map<PairNumber, TableRoundResult[]>;
    private boardAverages?: Map<BoardNumber, number>;

    private postponedTables: TableNumber[] = [];

    constructor(data: RoundData, public readonly number: number, public readonly rotation: RoundRotation) {
        if(data.date) 
            this.date = new Date(data.date);
        if (data.boardResults) 
            this.boardResults = Round.parseBoardResults(data.boardResults, rotation);

        this.boards = new Map<BoardNumber, Board>();
        if(data.boards !== undefined) {
            Object.keys(data.boards).forEach(num => {

                if(data.boards![num]!.deal.length !== 4) 
                {
                    console.warn(`Board ${num} in round ${this.number} has ${data.boards![num]!.deal.length} cards`);
                    return;
                }
                this.boards?.set(Number.parseInt(num), data.boards![num]!);
            })
        }

        const results = calculateResults(this, data);

        const overwrites = data.overwrites ?? [];

        (overwrites.filter((o) => o.type === "postponed") as ResultOverwritePostponed[])
        .forEach((o) => {
            this.postponedTables.push(o.table);
        });

        this.matchResultsByPair = new Map<PairNumber, TableRoundResult[]>();
        
        this.matchResultsByTable = this.applyOverwrites(
            overwrites,
            results
        );
        this.adjusts = overwrites.filter((o) => o.type === "adjust").map(
            o => ResultAdjustment.fromOverwrite(o as ResultAdjustmentOverwrite, this.number)
        ) ;


        this.matchResultsByTable.forEach((result, _) => {
            if (!this.matchResultsByPair!.has(result.ns)) {
                this.matchResultsByPair!.set(result.ns, []);
            }
            if (!this.matchResultsByPair!.has(result.ew)) {
                this.matchResultsByPair!.set(result.ew, []);
            }

            this.matchResultsByPair!.get(result.ns)!.push(result);
            this.matchResultsByPair!.get(result.ew)!.push(result);
        });

        if(data.averages !== undefined) {
            this.boardAverages = new Map<BoardNumber, number>();
            Object.keys(data.averages).forEach(num => {
                this.boardAverages?.set(Number.parseInt(num), data.averages![num]!);
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

    public getPairResults(pairNumber: PairNumber): PairTableRoundResult[] {
        const results = this.matchResultsByPair?.get(pairNumber);
        if (!results) return [];
        return results.map(r => new PairTableRoundResult(r, pairNumber));
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
        (overwrites
            .filter((o) => o.type === "ignore") as ResultOverwriteIgnore[])
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

        (overwrites
            .filter((o) => o.type === "imp") as ResultOverwriteIMP[])
            .forEach((o) => {
                const result = resultsByTable.get(o.table);
                if (!result) return;
                if (
                    result.status === "not-played" ||
                    result.status === "scratched"
                )
                    return;

                if (o.imp_diff_ns)
                    result.imp_ns += o.imp_diff_ns;
                if (o.imp_diff_ew)
                    result.imp_ew += o.imp_diff_ew;

                const vps = calculateVP(result.imp_ns - result.imp_ew);
                result.vp_ns = vps.ns;
                result.vp_ew = vps.ew;
            });

        (overwrites
            .filter((o) => o.type === "vp") as ResultOverwriteVP[])
            .forEach((o) => {
                const result = resultsByTable.get(o.table);
                if (!result) return;

                const new_result = { ...result };

                if(o.vp_ns !== undefined) (new_result as any).vp_ns = o.vp_ns;
                if(o.vp_ew !== undefined) (new_result as any).vp_ew = o.vp_ew;
                if(o.vp_ns !== undefined && o.vp_ew !== undefined) new_result.status = "scratched";

                resultsByTable.set(o.table, new_result);
            });

        (overwrites
            .filter((o) => o.type === "player") as ResultOverwritePlayer[])
            .forEach((o) => {
                const result = resultsByTable.get(o.table);
                if (!result) return;
                if (
                    result.status === "not-played" ||
                    result.status === "scratched"
                )
                    return;

                if (o.ns) result.ns_override = o.ns;
                if (o.ew) result.ew_override = o.ew;
            });


        return resultsByTable;
    }

    private static parseBoardResults(boardResults: BoardResultsPartial[], rotation: RoundRotation): BoardResult[] {
        return boardResults.map(r => {
            if ("table" in r && rotation[r.table] !== undefined) {
                const tableRotation = rotation[r.table];
                return { ...r, ns: tableRotation!.ns, ew: tableRotation!.ew } as BoardResult;
            }
            if ("ns" in r && "ew" in r && r.ns !== undefined && r.ew !== undefined) {
                return r as BoardResult;
            }
            return undefined;
        }).filter((r): r is BoardResult => r !== undefined);
    }
}
