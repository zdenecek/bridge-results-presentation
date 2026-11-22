import { describe, expect, it } from "vitest";
import { TournamentData } from "@/model/Tournament";
import TournamentFileParser from "@/parse/TournamentFileParser";

describe("PBN deals file", () => {
    it("reads LF line endings and does not carry analysis over to the next board", () => {
        const pbn = [
            '[Board "1"]', '[Dealer "N"]', '[Vulnerable "None"]',
            '[Deal "N:A42.A852.T653.92 K9875.9.984.JT83 3.KQJT643.AQ2.Q6 QJT6.7.KJ7.AK754"]',
            '[Ability "N:83983 E:4A348 S:83A83 W:4A358"]', '[Minimax "5HS-100"]',
            '[Board "2"]', '[Dealer "E"]', '[Vulnerable "NS"]',
            '[Deal "N:J5.AQJT86.J863.A KQ942.K973.A7.J8 T86..KT942.T9542 A73.542.Q5.KQ763"]',
        ].join("\n");
        const data = { rounds: { "1": { boards: {} } } } as unknown as TournamentData;
        const boards = TournamentFileParser.applyDealsFile(pbn, data, 1).rounds["1"]!.boards!;
        expect(boards["1"]!.ability).toEqual([8, 3, 9, 8, 3, 4, 10, 3, 4, 8, 8, 3, 10, 8, 3, 4, 10, 3, 5, 8]);
        expect(boards["1"]!.minimax).toBe("5HS-100");
        expect(boards["2"]!.deal[0]).toBe("J5.AQJT86.J863.A");
        expect(boards["2"]!.ability).toBeUndefined();
        expect(boards["2"]!.minimax).toBeUndefined();
    });
});
