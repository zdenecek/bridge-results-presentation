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

const BBO_BIDS: Record<string, string> = { PASS: "p", X: "d", XX: "r" };
const RANKS = "23456789TJQKA";

function parseGames(text: string): Record<string, string>[] {
    const games: Record<string, string>[] = [];
    let current: Record<string, string> = {};
    let section = "";
    for (const line of text.split(/\r?\n/)) {
        const m = /^\[(\w+)\s+"(.*)"\]\s*$/.exec(line);
        if (!m) {
            // Auction and Play tags are followed by lines of bids or tricks.
            if (section && line.trim()) current[section] = `${current[section] ?? ""} ${line.trim()}`;
            continue;
        }
        const tag = m[1]!;
        if (tag === "Event" || tag in current) {
            if (Object.keys(current).length) games.push(current);
            current = {};
        }
        current[tag] = m[2]!;
        section = tag === "Auction" || tag === "Play" ? `${tag}Lines` : "";
    }
    if (Object.keys(current).length) games.push(current);
    return games;
}

function bboAuction(lines: string | undefined): string | undefined {
    const bids: string[] = [];
    for (const token of (lines ?? "").trim().split(/\s+/)) {
        const t = token.toUpperCase();
        if (t === "AP") bids.push(...(bids.length ? ["p", "p", "p"] : ["p", "p", "p", "p"]));
        else if (BBO_BIDS[t]) bids.push(BBO_BIDS[t]!);
        else if (/^[1-7](C|D|H|S|NT)$/.test(t)) bids.push(t.replace("NT", "N"));
    }
    return bids.length ? bids.join(" ") : undefined;
}

/**
 * PBN lists each trick in fixed columns starting with the opening leader;
 * the play order starts with whoever won the previous trick.
 */
function playOrder(leader: string, lines: string | undefined, strain: string): string | undefined {
    const first = SEATS.indexOf(leader as PositionString);
    const cards = (lines ?? "").trim().split(/\s+/).filter(Boolean);
    if (first < 0 || cards.length < 4) return undefined;

    const trump = strain === "NT" ? undefined : strain;
    let lead = first;
    let play = "";
    for (let trick = 0; (trick + 1) * 4 <= cards.length; trick++) {
        const bySeat = new Map<number, string>();
        cards.slice(trick * 4, trick * 4 + 4).forEach((card, column) => bySeat.set((first + column) % 4, card.toUpperCase()));
        const order = [0, 1, 2, 3].map((i) => (lead + i) % 4);
        const played = order.map((seat) => bySeat.get(seat)!);
        if (played.some((c) => !/^[SHDC][2-9TJQKA]$/.test(c))) break;

        const led = played[0]![0];
        const beats = (a: string, b: string) =>
            a[0] === b[0] ? RANKS.indexOf(a[1]!) > RANKS.indexOf(b[1]!) : a[0] === trump;
        let winner = 0;
        played.forEach((card, i) => {
            const best = played[winner]!;
            if ((card[0] === led || card[0] === trump) && beats(card, best)) winner = i;
        });
        play += played.join("");
        lead = order[winner]!;
    }
    return play || undefined;
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
        const auction = bboAuction(game["AuctionLines"]);
        const play = playOrder(game["Play"] ?? "", game["PlayLines"], contract.strain);
        field.push({
            deal,
            contract: formatContract(contract),
            declarer,
            result: formatResult(overtricks),
            points: nsScore(contract, declarer, overtricks, vul),
            ...(auction && { auction }),
            ...(play && { play }),
        });
    }

    return { boards, field, skipped };
}
