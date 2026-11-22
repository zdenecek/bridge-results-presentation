import type { DdTable, DdsWorker, Par, Seat, Strain, Vulnerability } from 'bridge-dds-wasm';
import type { Board, BoardAnalysis } from '@/model/Board';
import type { RoundData } from '@/model/Round';

const SEATS: Seat[] = ['N', 'E', 'S', 'W'];
const STRAINS: Strain[] = ['NT', 'S', 'H', 'D', 'C'];

type Analysis = { table: DdTable; par: Par };
/** The solver in a worker, or synchronous in tests. */
type Analyser = {
    analyse(deal: string, options: { dealer: Seat; vulnerability: Vulnerability }): Analysis | Promise<Analysis>;
};

/** Tricks in the order of `Board.ability`: N, E, S, W, each NT, S, H, D, C. */
export function toAbility(table: DdTable): BoardAnalysis {
    return SEATS.flatMap((seat) => STRAINS.map((strain) => table[seat][strain]));
}

/** Par in the PBN `Minimax` form, e.g. `4SN420` or `5HDE-500` (score from North–South). */
export function toMinimax(par: Par, table: DdTable): string | undefined {
    const contract = par.contracts[0];
    if (!contract) return undefined;
    let declarer = contract.declarer;
    if (declarer === 'NS' || declarer === 'EW') {
        const [first, second] = declarer.split('') as [Seat, Seat];
        declarer = table[second][contract.strain] > table[first][contract.strain] ? second : first;
    }
    const strain = contract.strain === 'NT' ? 'N' : contract.strain;
    return `${contract.level}${strain}${contract.doubled ? 'D' : ''}${declarer}${par.score}`;
}

export function needsAnalysis(board: Board): boolean {
    return !board.ability && board.deal?.length === 4;
}

export function missingAnalysis(rounds: Record<string, RoundData> | undefined): number {
    return Object.values(rounds ?? {})
        .flatMap((round) => Object.values(round.boards ?? {}))
        .filter(needsAnalysis).length;
}

/** Fills `ability` and `minimax` of the boards that have none. Returns how many failed. */
export async function analyseBoards(
    boards: Board[],
    dds: Analyser,
    onProgress?: (done: number, total: number) => void,
): Promise<number> {
    const todo = boards.filter(needsAnalysis);
    let failed = 0;
    for (const [i, board] of todo.entries()) {
        try {
            const { table, par } = await dds.analyse(`N:${board.deal.join(' ')}`, {
                dealer: board.dealer as Seat,
                vulnerability: board.vul as Vulnerability,
            });
            board.ability = toAbility(table);
            const minimax = toMinimax(par, table);
            if (minimax) board.minimax = minimax;
        } catch (e) {
            failed++;
            console.error('Double dummy analysis failed', board, e);
        }
        onProgress?.(i + 1, todo.length);
    }
    return failed;
}

let worker: Promise<DdsWorker> | undefined;

/** The solver in a Web Worker, loaded the first time it is needed. */
export function ddsWorker(): Promise<DdsWorker> {
    worker ??= import('bridge-dds-wasm/client').then(({ connectDdsWorker }) => connectDdsWorker());
    return worker;
}
