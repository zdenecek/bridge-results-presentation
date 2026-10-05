import { Board, BoardNumberKey, PositionString } from "@/model/Board";
import { SessionLine } from "@/model/PostponedSession";
import { formatContract, formatResult, nsScore, parseContract } from "@/model/Scoring";

export type JackPbn = {
    boards: Record<BoardNumberKey, Board>;
    field: SessionLine[];
    skipped: number;
};

const SEATS: PositionString[] = ["N", "E", "S", "W"];

function normalizeVul(vul: string): string {
    const v = vul.trim().toUpperCase();
    if (v === "NS" || v === "EW") return v;
    if (v === "ALL" || v === "BOTH") return "All";
    return "None";
}

function parseDeal(deal: string): [string, string, string, string] | undefined {
    const m = /^([NESW]):(.+)$/.exec(deal.trim());
    if (!m) return undefined;
    const hands = m[2]!.trim().split(/\s+/);
    if (hands.length !== 4) return undefined;
    const first = SEATS.indexOf(m[1] as PositionString);
    return SEATS.map((_, i) => hands[(i - first + 4) % 4]!) as [string, string, string, string];
}

function parseGames(text: string): Record<string, string>[] {
    const games: Record<string, string>[] = [];
    let current: Record<string, string> = {};
    for (const line of text.split(/\r?\n/)) {
        const m = /^\[(\w+)\s+"(.*)"\]\s*$/.exec(line);
        if (!m) continue;
        const tag = m[1]!;
        if (tag === "Event" || tag in current) {
            if (Object.keys(current).length) games.push(current);
            current = {};
        }
        current[tag] = m[2]!;
    }
    if (Object.keys(current).length) games.push(current);
    return games;
}

/** Reads a PBN with several played copies of each board, as saved by Jack. */
export function parseJackPbn(text: string): JackPbn {
    const boards: Record<BoardNumberKey, Board> = {};
    const field: SessionLine[] = [];
    let skipped = 0;

    for (const game of parseGames(text)) {
        const deal = Number.parseInt(game["Board"] ?? "");
        if (isNaN(deal)) continue;
        const key = deal.toString();
        const vul = normalizeVul(game["Vulnerable"] ?? "");

        if (!boards[key]) {
            const hands = parseDeal(game["Deal"] ?? "");
            if (hands) boards[key] = { dealer: game["Dealer"] ?? "", vul, deal: hands };
        }

        const contract = parseContract(game["Contract"] ?? "");
        const declarer = (game["Declarer"] ?? "").trim().toUpperCase() as PositionString;
        const tricks = Number.parseInt(game["Result"] ?? "");
        if (contract === undefined || game["Contract"] === undefined) {
            skipped++;
            continue;
        }
        if (contract === "pass") {
            field.push({ deal, contract: "PASS", declarer: "", result: "", points: 0 });
            continue;
        }
        if (!SEATS.includes(declarer) || isNaN(tricks)) {
            skipped++;
            continue;
        }
        const overtricks = tricks - (contract.level + 6);
        field.push({
            deal,
            contract: formatContract(contract),
            declarer,
            result: formatResult(overtricks),
            points: nsScore(contract, declarer, overtricks, vul),
        });
    }

    return { boards, field, skipped };
}
