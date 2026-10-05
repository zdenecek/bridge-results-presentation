import { Board, BoardNumber, BoardNumberKey } from "./Board";
import { ResultOverwritePostponed } from "./Overwrites";
import { impsForDifference, trimmedDatum } from "./Scoring";
import type { TournamentData } from "./Tournament";
import { RoundNumber, TableNumber } from "./modelTypes";

export type SessionLine = {
    deal: BoardNumber;
    contract: string;
    declarer: string;
    result: string;
    points: number;
    /** Bids from the dealer in BBO notation: "p 1S p 2S d 4S p p p". */
    auction?: string;
    /** Cards in the order they were played: "C9C2CJCASKS6…". */
    play?: string;
};

export type PostponedMatchData = {
    round: RoundNumber;
    table: TableNumber;
    /** The pair seated NS in the rotation played EW. */
    swapped?: boolean;
    slip: string;
    results: SessionLine[];
};

/** Postponed matches played on one set of boards, scored against a Jack-simulated field. */
export type PostponedSessionData = {
    id: string;
    date?: string;
    boards: Record<BoardNumberKey, Board>;
    field: SessionLine[];
    matches: PostponedMatchData[];
};

export type ScoredLine = SessionLine & {
    imp: number;
    /** Index into `matches`; undefined for Jack lines. */
    match?: number;
};

export type PostponedMatchResult = {
    match: PostponedMatchData;
    index: number;
    lines: ScoredLine[];
    /** IMPs of the pairs as seated in the rotation. */
    imp_ns: number;
    imp_ew: number;
    /** IMPs of the pairs as they sat at the table. */
    seated_imp_ns: number;
    seated_imp_ew: number;
};

export class PostponedSession {
    readonly id: string;
    readonly date?: Date;
    readonly boards = new Map<BoardNumber, Board>();
    readonly averages = new Map<BoardNumber, number>();
    readonly matchResults: PostponedMatchResult[];
    private readonly linesByBoard = new Map<BoardNumber, ScoredLine[]>();

    constructor(public readonly data: PostponedSessionData) {
        this.id = data.id;
        if (data.date) this.date = new Date(data.date);
        Object.entries(data.boards).forEach(([num, board]) => this.boards.set(Number.parseInt(num), board));

        const all: (SessionLine & { match?: number })[] = [
            ...data.field,
            ...data.matches.flatMap((m, index) => m.results.map((r) => ({ ...r, match: index }))),
        ];
        const pointsByBoard = new Map<BoardNumber, number[]>();
        all.forEach((l) => pointsByBoard.set(l.deal, [...(pointsByBoard.get(l.deal) ?? []), l.points]));
        pointsByBoard.forEach((points, board) => this.averages.set(board, trimmedDatum(points)!));

        all.forEach((l) => {
            const scored: ScoredLine = { ...l, imp: impsForDifference(l.points - this.averages.get(l.deal)!) };
            this.linesByBoard.set(l.deal, [...(this.linesByBoard.get(l.deal) ?? []), scored]);
        });
        this.linesByBoard.forEach((lines) => lines.sort((a, b) => b.points - a.points));

        this.matchResults = data.matches.map((match, index) => {
            const lines = [...this.linesByBoard.values()]
                .flat()
                .filter((l) => l.match === index)
                .sort((a, b) => a.deal - b.deal);
            const seatedNs = lines.reduce((acc, l) => acc + Math.max(l.imp, 0), 0);
            const seatedEw = lines.reduce((acc, l) => acc + Math.max(-l.imp, 0), 0);
            return {
                match,
                index,
                lines,
                imp_ns: match.swapped ? seatedEw : seatedNs,
                imp_ew: match.swapped ? seatedNs : seatedEw,
                seated_imp_ns: seatedNs,
                seated_imp_ew: seatedEw,
            };
        });
    }

    get boardNumbers(): BoardNumber[] {
        return [...this.linesByBoard.keys()].sort((a, b) => a - b);
    }

    get fieldTables(): number {
        return Math.max(0, ...[...this.boards.keys()].map((b) => this.data.field.filter((l) => l.deal === b).length));
    }

    getBoardLines(board: BoardNumber): ScoredLine[] {
        return this.linesByBoard.get(board) ?? [];
    }
}

/**
 * Writes the session's match IMPs into the rounds' postponed overwrites, creating
 * them when missing. Overwrites linked to the session but no longer matched are reset.
 */
export function writeSessionToRounds(data: TournamentData, session: PostponedSession): void {
    Object.values(data.rounds).forEach((round) =>
        (round.overwrites ?? [])
            .filter((o): o is ResultOverwritePostponed => o.type === "postponed" && o.session === session.id)
            .forEach((o) => {
                delete o.imp_ns;
                delete o.imp_ew;
                delete o.session;
            })
    );

    session.matchResults
        .filter((r) => r.lines.length > 0)
        .forEach((r) => {
            const round = data.rounds[r.match.round.toString()];
            if (!round) return;
            round.overwrites ??= [];
            let overwrite = round.overwrites.find(
                (o): o is ResultOverwritePostponed => o.type === "postponed" && o.table === r.match.table
            );
            if (!overwrite) {
                overwrite = { type: "postponed", table: r.match.table };
                round.overwrites.push(overwrite);
            }
            overwrite.imp_ns = r.imp_ns;
            overwrite.imp_ew = r.imp_ew;
            overwrite.session = session.id;
            if (session.data.date) overwrite.date = session.data.date;
        });
}
