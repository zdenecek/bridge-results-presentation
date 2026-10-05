import { Board, BoardNumberKey, PositionString } from "@/model/Board";
import { SessionLine } from "@/model/PostponedSession";
import { formatContract, formatResult, nsScore, parseContract } from "@/model/Scoring";

export type SlipIssue = {
    line: number;
    text: string;
    message: string;
    error: boolean;
};

export type ParsedSlip = {
    results: SessionLine[];
    issues: SlipIssue[];
};

const SYMBOLS: Record<string, string> = {
    "♠": "S", "♤": "S", "♥": "H", "♡": "H", "♦": "D", "♢": "D", "♣": "C", "♧": "C", "−": "-", "–": "-",
};

const LINE_RE = /^(\d+)([.:)]|\s)?\s*(.*)$/;
const RESULT_RE = /^([1-7])\s*(NT|N|C|D|H|S)\s*(XX|X|R)?\s*([NESW])\s*(XX|X|R)?\s*(=|[+-]\s*\d{1,2})(?:\s+(-?\d+))?$/;
const PASS_RE = /^(PASS|PAS|P)(?:\s+0)?$/;

type LineOutcome = { result?: SessionLine; error?: string; warning?: string };

/**
 * Splits the board number from the rest. A space or `.` ends the board number,
 * otherwise its last digit is the contract level: `14SW-1` is board 1, `144SW-1` board 14.
 */
function splitBoard(normalized: string): { deal: number; rest: string } | undefined {
    const m = LINE_RE.exec(normalized);
    if (!m) return undefined;
    const digits = m[1]!;
    const rest = m[3]!;
    if (m[2] || PASS_RE.test(rest)) return digits.length <= 2 ? { deal: Number.parseInt(digits), rest } : undefined;
    if (digits.length < 2 || digits.length > 3) return undefined;
    return { deal: Number.parseInt(digits.slice(0, -1)), rest: digits.slice(-1) + rest };
}

function parseLine(normalized: string, boards: Record<BoardNumberKey, Board>): LineOutcome {
    const split = splitBoard(normalized);
    if (!split) return { error: "Nerozumím řádku, čekám např. „1 4SW -1“" };

    const { deal, rest } = split;
    const board = boards[deal.toString()];
    if (!board) return { error: `Rozdání ${deal} není v PBN` };

    if (PASS_RE.test(rest)) return { result: { deal, contract: "PASS", declarer: "", result: "", points: 0 } };

    const r = RESULT_RE.exec(rest);
    if (!r) return { error: "Nerozumím závazku, čekám např. „4SW -1“ nebo „3NTS =“" };
    const contract = parseContract(`${r[1]}${r[2]}${r[3] ?? r[5] ?? ""}`);
    if (!contract || contract === "pass") return { error: "Neplatný závazek" };
    const declarer = r[4] as PositionString;
    const overtricks = r[6] === "=" ? 0 : Number.parseInt(r[6]!.replace(/\s/g, ""));
    if (overtricks > 7 - contract.level || overtricks < -(contract.level + 6))
        return { error: "Nemožný počet zdvihů" };

    const points = nsScore(contract, declarer, overtricks, board.vul);
    const result = {
        deal,
        contract: formatContract(contract),
        declarer,
        result: formatResult(overtricks),
        points,
    };
    if (r[7] !== undefined && Math.abs(Number.parseInt(r[7])) !== Math.abs(points))
        return { result, warning: `Zapsáno ${r[7]}, ze závazku vychází ${Math.abs(points)}` };
    return { result };
}

/**
 * One board per line, spaces optional: `1 4SW -1`, `14SW-1`, `2 6♦W+1`, `13 3NTS =`, `9pass`.
 * The score is computed from the contract. A score written after the result
 * (e.g. by an AI transcribing a photo of the slip) is only checked against it, sign ignored.
 */
export function parseSlip(text: string, boards: Record<BoardNumberKey, Board>): ParsedSlip {
    const results: SessionLine[] = [];
    const issues: SlipIssue[] = [];

    text.split(/\r?\n/).forEach((raw, index) => {
        const trimmed = raw.trim();
        if (trimmed === "" || trimmed.startsWith("#")) return;
        const issue = (message: string, error: boolean) =>
            issues.push({ line: index + 1, text: trimmed, message, error });

        const normalized = [...trimmed].map((c) => SYMBOLS[c] ?? c).join("").toUpperCase().replace(/BT|SA/g, "NT");
        const { result, error, warning } = parseLine(normalized, boards);
        if (error) issue(error, true);
        if (warning) issue(warning, false);
        if (!result) return;
        if (results.some((r) => r.deal === result.deal)) issue(`Rozdání ${result.deal} je zapsané dvakrát`, true);
        else results.push(result);
    });

    return { results, issues };
}
