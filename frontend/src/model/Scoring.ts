import { PositionString } from "./Board";

export type Strain = "C" | "D" | "H" | "S" | "NT";

export type Contract = {
    level: number;
    strain: Strain;
    doubled: 0 | 1 | 2;
};

const IMP_THRESHOLDS = [
    20, 50, 90, 130, 170, 220, 270, 320, 370, 430, 500, 600,
    750, 900, 1100, 1300, 1500, 1750, 2000, 2250, 2500, 3000, 3500, 4000,
];

export function impsForDifference(diff: number): number {
    const abs = Math.abs(diff);
    const imps = IMP_THRESHOLDS.filter((t) => abs >= t).length;
    return diff < 0 && imps > 0 ? -imps : imps;
}

/**
 * Datum used by the club's scoring program: 10 % of the results (by weight) are cut
 * from each end of the sorted field, the boundary result counting partially,
 * and the weighted mean is rounded to the nearest 10 (half away from zero).
 */
export function trimmedDatum(points: number[]): number | undefined {
    const n = points.length;
    if (n === 0) return undefined;

    // Every result weighs 10 units, n units (= 10 %) are removed from each end.
    const sorted = [...points].sort((a, b) => a - b);
    const weights = sorted.map(() => 10);
    let cutLow = n;
    for (let i = 0; i < n && cutLow > 0; i++) {
        const cut = Math.min(weights[i]!, cutLow);
        weights[i] = weights[i]! - cut;
        cutLow -= cut;
    }
    let cutHigh = n;
    for (let i = n - 1; i >= 0 && cutHigh > 0; i--) {
        const cut = Math.min(weights[i]!, cutHigh);
        weights[i] = weights[i]! - cut;
        cutHigh -= cut;
    }

    const num = sorted.reduce((acc, p, i) => acc + p * weights[i]!, 0);
    const den = weights.reduce((acc, w) => acc + w, 0);
    const tens = Math.floor((2 * Math.abs(num) + 10 * den) / (20 * den));
    return num < 0 ? -tens * 10 : tens * 10;
}

export function isVulnerable(vul: string, declarer: PositionString): boolean {
    const v = vul.trim().toUpperCase();
    if (v === "ALL" || v === "BOTH") return true;
    if (v === "NS") return declarer === "N" || declarer === "S";
    if (v === "EW") return declarer === "E" || declarer === "W";
    return false;
}

/** Score for the declaring side; `overtricks` is negative for undertricks. */
export function declarerScore(contract: Contract, overtricks: number, vulnerable: boolean): number {
    const { level, strain, doubled } = contract;

    if (overtricks < 0) {
        const down = -overtricks;
        if (doubled === 0) return -down * (vulnerable ? 100 : 50);
        let penalty: number;
        if (vulnerable) penalty = 200 + 300 * (down - 1);
        else penalty = [100, 300, 500][Math.min(down, 3) - 1]! + 300 * Math.max(0, down - 3);
        return -penalty * doubled;
    }

    const perTrick = strain === "C" || strain === "D" ? 20 : 30;
    const trickPoints = (perTrick * level + (strain === "NT" ? 10 : 0)) * 2 ** doubled;

    let score = trickPoints;
    score += trickPoints >= 100 ? (vulnerable ? 500 : 300) : 50;
    if (level === 6) score += vulnerable ? 750 : 500;
    if (level === 7) score += vulnerable ? 1500 : 1000;
    score += 50 * doubled;
    score += doubled === 0
        ? overtricks * perTrick
        : overtricks * (vulnerable ? 200 : 100) * doubled;
    return score;
}

export function nsScore(
    contract: Contract | "pass",
    declarer: PositionString | undefined,
    overtricks: number,
    vul: string
): number {
    if (contract === "pass" || !declarer) return 0;
    const score = declarerScore(contract, overtricks, isVulnerable(vul, declarer));
    return declarer === "N" || declarer === "S" ? score : -score;
}

/** Accepts "4S", "4SX", "3NT", "3 NT xx", "Pass"; undefined when not a contract. */
export function parseContract(text: string): Contract | "pass" | undefined {
    const t = text.replace(/\s+/g, "").toUpperCase();
    if (t === "" || t === "P" || t === "PASS") return "pass";
    const m = /^([1-7])(NT|N|C|D|H|S)(XX|X|R)?$/.exec(t);
    if (!m) return undefined;
    return {
        level: Number.parseInt(m[1]!),
        strain: (m[2] === "N" ? "NT" : m[2]) as Strain,
        doubled: m[3] === undefined ? 0 : m[3] === "X" ? 1 : 2,
    };
}

export function formatContract(contract: Contract | "pass"): string {
    if (contract === "pass") return "PASS";
    return `${contract.level}${contract.strain}${"X".repeat(contract.doubled)}`;
}

export function formatResult(overtricks: number): string {
    if (overtricks === 0) return "=";
    return overtricks > 0 ? `+${overtricks}` : `${overtricks}`;
}
