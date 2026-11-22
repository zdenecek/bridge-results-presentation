import { describe, expect, it } from "vitest";
import { createDds, type DdTable } from "bridge-dds-wasm";
import { Board } from "@/model/Board";
import { analyseBoards, missingAnalysis, toAbility, toMinimax } from "@/utils/ddAnalysis";

// Sacrifices from Skupinová A Podzim 2025; the PBN files wrote them without the D.
const published: { board: Board; minimax: string }[] = [
    {
        board: { dealer: "S", vul: "None", deal: ["A42.A852.T653.92", "K9875.9.984.JT83", "3.KQJT643.AQ2.Q6", "QJT6.7.KJ7.AK754"], ability: [8, 3, 9, 8, 3, 4, 10, 3, 4, 8, 8, 3, 10, 8, 3, 4, 10, 3, 5, 8] },
        minimax: "5HDS-100",
    },
    {
        board: { dealer: "S", vul: "All", deal: ["J5.AQJT86.J863.A", "KQ942.K973.A7.J8", "T86..KT942.T9542", "A73.542.Q5.KQ763"], ability: [4, 4, 8, 9, 4, 9, 8, 4, 3, 9, 4, 4, 8, 9, 4, 7, 8, 4, 2, 8] },
        minimax: "4DDN-200",
    },
    {
        board: { dealer: "S", vul: "None", deal: ["J432.A8.5432.J82", "Q9.KQ9.AQ987.Q97", "AKT87.762.KJ.643", "65.JT543.T6.AKT5"], ability: [6, 8, 4, 4, 4, 6, 5, 9, 8, 9, 6, 8, 4, 4, 4, 6, 5, 9, 8, 9] },
        minimax: "3SDN-100",
    },
];

const unanalysed = () => published.map(({ board }) => ({ ...board, ability: undefined }) as Board);

const table = (rows: Record<string, number[]>): DdTable =>
    Object.fromEntries(Object.entries(rows).map(([seat, t]) => [seat, { NT: t[0], S: t[1], H: t[2], D: t[3], C: t[4] }])) as DdTable;

describe("double dummy analysis", () => {
    it("matches the tables and par that came with the PBN files", async () => {
        const boards = unanalysed();
        const failed = await analyseBoards(boards, await createDds());
        expect(failed).toBe(0);
        boards.forEach((board, i) => {
            expect(board.ability).toEqual(published[i]!.board.ability);
            expect(board.minimax).toBe(published[i]!.minimax);
        });
    });

    it("leaves boards that already have analysis alone", async () => {
        const boards = [{ ...published[0]!.board, ability: [1], minimax: "keep" }];
        const calls: string[] = [];
        await analyseBoards(boards, { analyse: (deal) => { calls.push(deal); throw new Error("not called"); } });
        expect(calls).toEqual([]);
        expect(boards[0]!.minimax).toBe("keep");
    });

    it("counts boards without analysis across rounds and dohrávky", () => {
        const [a, b, c] = unanalysed();
        expect(missingAnalysis({
            rounds: { "1": { boards: { "1": a!, "2": published[0]!.board } }, "2": { boards: { "1": b! } }, "3": {} },
            postponedSessions: [{ id: "s", date: "2026-10-05", boards: { "1": c! }, field: [], matches: [] }],
        })).toBe(3);
    });

    it("orders the ability array N, E, S, W by NT, S, H, D, C", () => {
        const t = table({ N: [1, 2, 3, 4, 5], E: [6, 7, 8, 9, 10], S: [11, 12, 13, 0, 1], W: [2, 3, 4, 5, 6] });
        expect(toAbility(t)).toEqual([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 0, 1, 2, 3, 4, 5, 6]);
    });

    it("names the partner who takes more tricks, else North or East", () => {
        const t = table({ N: [9, 8, 8, 8, 8], E: [4, 4, 4, 4, 4], S: [10, 9, 8, 8, 8], W: [3, 4, 4, 4, 4] });
        const contract = { level: 3, strain: "NT" as const, declarer: "NS" as const, doubled: false, result: 0 };
        expect(toMinimax({ score: 400, contracts: [contract] }, t)).toBe("3NS400");
        expect(toMinimax({ score: 140, contracts: [{ ...contract, level: 2, strain: "H" }] }, t)).toBe("2HN140");
        expect(toMinimax({ score: 0, contracts: [] }, t)).toBeUndefined();
    });
});
