import { readFileSync } from "fs";
import { resolve } from "path";
import { describe, expect, it } from "vitest";
import { Board } from "@/model/Board";
import { PostponedSession, writeSessionToRounds } from "@/model/PostponedSession";
import { TournamentData } from "@/model/Tournament";
import { impsForDifference, trimmedDatum } from "@/model/Scoring";
import { parseJackPbn } from "@/parse/JackPbnParser";
import { parseSlip } from "@/parse/SlipParser";

const fixture = (name: string) => readFileSync(resolve(__dirname, "fixtures", name), "utf-8");

const VUL_CYCLE = ["None", "NS", "EW", "All", "NS", "EW", "All", "None", "EW", "All", "None", "NS", "All", "None", "NS", "EW"];
const standardBoards = (count: number): Record<string, Board> =>
    Object.fromEntries(
        Array.from({ length: count }, (_, i) => [
            (i + 1).toString(),
            { vul: VUL_CYCLE[i % 16]!, dealer: "NESW"[i % 4]!, deal: ["", "", "", ""] },
        ])
    );

describe("datum and IMPs match the club's scoring program", () => {
    const golden = JSON.parse(fixture("skupinovka-datums.json")) as {
        boards: { points: number[]; average: number }[];
        imps: [number, number][];
    };

    it("trims 10 % from each end and rounds to 10", () => {
        golden.boards.forEach(({ points, average }) => expect(trimmedDatum(points)).toBe(average));
    });

    it("converts differences to IMPs", () => {
        golden.imps.forEach(([diff, imps]) => expect(impsForDifference(diff)).toBe(imps));
    });
});

describe("slip parser", () => {
    // Travelling slip of a postponed match from 29. 9. 2026, with the scores written on it.
    const slip = `
        1 4SW -1 50
        2 6♦W +1 940
        3 4♣N = 130
        4 3DN = 110
        5 1NTN -1 100
        6 5HN -2 100
        7 3NTS -1 100
        8 1NTE +1 120
        9 5DE = 600
        10 1NTN -1 100
        11 4DS = 130
        12 4SN +2 680
        13 3BTS = 600
        14 4SxW -2 300
        15 2SS+1 140
        16 1NT S +1 120
        17 3NTS= 400
        18 3NTE -1 50
        19 3CE = 110
        20 5CXS -3 800
        21 3NTxS +1 950
        22 3NTW -1 100
        23 1SN = 80
        24 4SS +2 480
        25 4SS -4 200
        26 2SW = 110
        27 4CS = 130
        28 1NTN = 90`;

    it("reads every notation and agrees with the written scores", () => {
        const parsed = parseSlip(slip, standardBoards(28));
        expect(parsed.issues).toEqual([]);
        expect(parsed.results).toHaveLength(28);
        expect(parsed.results[1]).toEqual({ deal: 2, contract: "6D", declarer: "W", result: "+1", points: -940 });
        expect(parsed.results[19]).toEqual({ deal: 20, contract: "5CX", declarer: "S", result: "-3", points: -800 });
        expect(parsed.results[20]!.points).toBe(950);
    });

    it("flags misread lines", () => {
        const parsed = parseSlip("2 6HW +1 940\n3 4XN\n3 4CN =\n3 4CN =\n29 1NTN =\n5 pass", standardBoards(28));
        expect(parsed.issues.map((i) => [i.line, i.error])).toEqual([
            [1, false],
            [2, true],
            [4, true],
            [5, true],
        ]);
        expect(parsed.results.map((r) => r.deal)).toEqual([2, 3, 5]);
    });
});

describe("Jack PBN", () => {
    const jack = parseJackPbn(fixture("jack-boards-1-2.pbn"));

    it("reads deals and every played copy", () => {
        expect(Object.keys(jack.boards)).toEqual(["1", "2"]);
        expect(jack.boards["2"]).toMatchObject({ dealer: "E", vul: "NS" });
        expect(jack.boards["1"]!.deal[0]).toBe("Q.K8643.KQT5.J65");
        expect(jack.field).toHaveLength(30);
        expect(jack.skipped).toBe(0);
        expect(jack.field.filter((l) => l.deal === 1).every((l) => l.points === -450)).toBe(true);
        expect(jack.field.filter((l) => l.deal === 2).map((l) => l.points)).toEqual([
            790, 100, -200, -200, 300, -200, -200, -200, 990, -200, 100, 790, -200, -200, 990,
        ]);
    });

    it("rotates deals that do not start with North", () => {
        const pbn = `[Board "1"]\n[Dealer "E"]\n[Vulnerable "Both"]\n[Deal "E:e.e.e.e s.s.s.s w.w.w.w n.n.n.n"]\n[Contract "Pass"]`;
        const parsed = parseJackPbn(pbn);
        expect(parsed.boards["1"]).toEqual({ dealer: "E", vul: "All", deal: ["n.n.n.n", "e.e.e.e", "s.s.s.s", "w.w.w.w"] });
        expect(parsed.field).toEqual([{ deal: 1, contract: "PASS", declarer: "", result: "", points: 0 }]);
    });
});

describe("postponed session", () => {
    const jack = parseJackPbn(fixture("jack-boards-1-2.pbn"));
    const slip = "1 4SW -1 50\n2 6DW +1 940";

    const session = (swapped: boolean) =>
        new PostponedSession({
            id: "test",
            boards: jack.boards,
            field: jack.field,
            matches: [{ round: 3, table: 5, swapped, slip, results: parseSlip(slip, jack.boards).results }],
        });

    it("includes the slips in the field and IMPs them against the datum", () => {
        const s = session(false);
        expect(s.averages.get(1)).toBe(-450);
        expect(s.averages.get(2)).toBe(80);
        expect(s.matchResults[0]!.lines.map((l) => l.imp)).toEqual([11, -14]);
        expect(s.matchResults[0]).toMatchObject({ imp_ns: 11, imp_ew: 14 });
        expect(s.getBoardLines(2)).toHaveLength(16);
        expect(s.fieldTables).toBe(15);
    });

    it("credits the rotation's pairs when they sat the other way", () => {
        expect(session(true).matchResults[0]).toMatchObject({ imp_ns: 14, imp_ew: 11 });
    });

    it("averages over the slips of every table in the session", () => {
        const second = "1 3NTE = 400\n2 4SXS = 790";
        const s = new PostponedSession({
            id: "two",
            boards: jack.boards,
            field: jack.field,
            matches: [
                { round: 3, table: 5, slip, results: parseSlip(slip, jack.boards).results },
                { round: 4, table: 2, slip: second, results: parseSlip(second, jack.boards).results },
            ],
        });
        // Board 2 alone with the first table averages 80, alone with the second 160.
        expect(s.averages.get(1)).toBe(-450);
        expect(s.averages.get(2)).toBe(130);
        expect(s.getBoardLines(2)).toHaveLength(17);
        expect(s.matchResults[0]!.lines.map((l) => l.imp)).toEqual([11, -14]);
        expect(s.matchResults[1]!.lines.map((l) => l.imp)).toEqual([2, 12]);
        expect(s.matchResults[0]).toMatchObject({ imp_ns: 11, imp_ew: 14 });
        expect(s.matchResults[1]).toMatchObject({ imp_ns: 14, imp_ew: 0 });
    });

    it("writes each table's IMPs into its round and resets dropped tables", () => {
        const second = "1 3NTE = 400\n2 4SXS = 790";
        const data = {
            rounds: {
                "3": { overwrites: [{ type: "postponed", table: 5 }] },
                "4": { overwrites: [] },
            },
        } as unknown as TournamentData;
        const sessionData = {
            id: "two",
            date: "2026-09-29",
            boards: jack.boards,
            field: jack.field,
            matches: [
                { round: 3, table: 5, slip, results: parseSlip(slip, jack.boards).results },
                { round: 4, table: 2, slip: second, results: parseSlip(second, jack.boards).results },
            ],
        };

        writeSessionToRounds(data, new PostponedSession(sessionData));
        expect(data.rounds["3"]!.overwrites).toEqual([
            { type: "postponed", table: 5, imp_ns: 11, imp_ew: 14, session: "two", date: "2026-09-29" },
        ]);
        expect(data.rounds["4"]!.overwrites).toEqual([
            { type: "postponed", table: 2, imp_ns: 14, imp_ew: 0, session: "two", date: "2026-09-29" },
        ]);

        writeSessionToRounds(data, new PostponedSession({ ...sessionData, matches: sessionData.matches.slice(0, 1) }));
        expect(data.rounds["4"]!.overwrites).toEqual([{ type: "postponed", table: 2, date: "2026-09-29" }]);
    });

    it("averages Jack alone when no slip covers the board", () => {
        const s = new PostponedSession({ id: "t", boards: jack.boards, field: jack.field, matches: [] });
        expect(s.averages.get(2)).toBe(110);
    });
});
