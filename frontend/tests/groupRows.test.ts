import { describe, expect, it } from "vitest";
import { groupRows } from "../src/utils/groupRows";

const names = (list: string[]) => groupRows(list.map((name) => ({ name }))).map((row) => row.map((g) => g.group.name));

describe("groupRows", () => {
    it("puts parallel groups side by side", () => {
        expect(names(["A1", "A21", "A22", "A31", "A32"])).toEqual([["A1"], ["A21", "A22"], ["A31", "A32"]]);
    });

    it("keeps single-digit levels apart", () => {
        expect(names(["B1", "B2", "B3"])).toEqual([["B1"], ["B2"], ["B3"]]);
    });

    it("keeps the original index", () => {
        expect(groupRows([{ name: "A1" }, { name: "A21" }, { name: "A22" }])[1]!.map((g) => g.index)).toEqual([1, 2]);
    });
});
