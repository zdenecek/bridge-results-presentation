import { describe, expect, it } from "vitest";
import { Tournament, TournamentData } from "@/model/Tournament";

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
