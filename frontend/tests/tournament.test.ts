import { describe, expect, it } from "vitest";
import { ref } from "vue";
import { Tournament, TournamentData } from "@/model/Tournament";
import { useImpSort } from "@/utils/impSort";

// Round 1 is played, round 2 is not, but one of its matches was played in advance.
const data = {
    title: "Test",
    totalRounds: 2,
    groups: [{ name: "A1", players: [1, 2, 3, 4] }],
    players: Object.fromEntries([1, 2, 3, 4].map((id) => [id, { id, title: `Pair ${id}`, players: [] }])),
    rotations: {
        "1": { "1": { ns: 1, ew: 2 }, "2": { ns: 3, ew: 4 } },
        "2": { "1": { ns: 1, ew: 3 }, "2": { ns: 2, ew: 4 } },
    },
    rounds: {
        "1": {
            boardResults: [
                { status: "played", deal: 1, ns: 1, ew: 2, contract: "3NT", declarer: "S", result: "=", points: 400, res_ns: 5, res_ew: -5 },
                { status: "played", deal: 1, ns: 3, ew: 4, contract: "3NT", declarer: "S", result: "-1", points: -50, res_ns: -6, res_ew: 6 },
            ],
        },
        "2": {
            boardResults: [],
            overwrites: [{ type: "postponed", table: 1, imp_ns: 20, imp_ew: 4, session: "s1" }],
        },
    },
} as unknown as TournamentData;

describe("match played ahead of its round", () => {
    it("shows in the pair's matches and totals, not in standings after round 1", () => {
        const t = new Tournament(data);
        expect(t.standing).toBe(1);

        const matches = t.getPairRoundResults(1);
        expect(matches.map((m) => [m.round, m.status])).toEqual([[1, "played"], [2, "postponed"]]);
        expect(t.getPairRoundResults(2).map((m) => m.round)).toEqual([1]);

        const afterRound1 = t.getPairResult(1, 1).vp;
        const all = t.getPairResult(1).vp;
        expect(all).toBeGreaterThan(afterRound1);
        expect(t.getPairResult(1, 1).vp).toBe(afterRound1);
        expect(t.getPairResult(1).matchCount).toBe(2);
    });

    it("lets the unplayed round show its results", () => {
        const t = new Tournament(data);
        expect(t.getRound(2)!.wasPlayed).toBe(false);
        expect(t.getRound(2)!.hasMatchResults).toBe(true);
        expect(t.getRound(2)!.getTableResult(1)).toMatchObject({ status: "postponed", session: "s1", imp_ns: 20 });
    });
});

describe("matches to be played", () => {
    // Round 1 is played except table 2, postponed to 20. 10.; round 2 is still ahead.
    const pending = {
        ...data,
        rounds: {
            "1": {
                date: "2026-09-21",
                boardResults: [data.rounds["1"]!.boardResults![0]],
                overwrites: [{ type: "postponed", table: 2, date: "2026-10-20" }],
            },
            "2": { date: "2026-10-05", boardResults: [] },
        },
    } as unknown as TournamentData;

    it("lists future rounds and pending postponed matches", () => {
        const t = new Tournament(pending);
        expect(t.getPairUpcomingMatches(3)).toEqual([
            { round: 1, table: 2, date: new Date("2026-10-20"), opponent: 4, ns: true, postponed: true },
            { round: 2, table: 1, date: new Date("2026-10-05"), opponent: 1, ns: false, postponed: false },
        ]);
        expect(t.getPairUpcomingMatches(1).map((m) => m.round)).toEqual([2]);
        expect(t.getRound(1)!.isTablePending(2)).toBe(true);
        expect(t.getRound(1)!.isTablePending(1)).toBe(false);
    });

    it("leaves out matches already played ahead of their round", () => {
        expect(new Tournament(data).getPairUpcomingMatches(1)).toEqual([]);
        expect(new Tournament(data).getPairUpcomingMatches(2).map((m) => m.round)).toEqual([2]);
    });
});

describe("score card sorting", () => {
    it("sorts by IMPs both ways with unscored boards last, and back by board", () => {
        const rows = ref([{ b: 1, i: 3 }, { b: 2, i: undefined }, { b: 3, i: -5 }, { b: 4, i: 7 }]);
        const { sorted, sortBy, indicator } = useImpSort(rows, (r) => r.b, (r) => r.i);
        expect(sorted.value.map((r) => r.b)).toEqual([1, 2, 3, 4]);
        sortBy("imp");
        expect(sorted.value.map((r) => r.b)).toEqual([4, 1, 3, 2]);
        expect(indicator("imp")).toBe("▼");
        sortBy("imp");
        expect(sorted.value.map((r) => r.b)).toEqual([3, 1, 4, 2]);
        sortBy("board");
        expect(sorted.value.map((r) => r.b)).toEqual([1, 2, 3, 4]);
    });
});
