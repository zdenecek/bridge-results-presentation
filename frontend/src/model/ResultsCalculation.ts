import { CompleteBoardResult } from "./BoardResult";
import {  PairSumResult, PlayedMatchResult, TableRoundResult } from "./MatchResult";
import { Round, RoundData } from "./Round";
import { PairNumber, TableNumber } from "./modelTypes";
import { calculateVP } from "./VP";
import { Rank } from "./Rank";
import { Tournament } from "./Tournament";



export function createPairSumResultComparator(tournament: Tournament): (a: PairSumResult, b: PairSumResult) => number {
    return tournament.settings.rankByAverage ?
        (a, b) => b.averageAsNumber - a.averageAsNumber
        : (a, b) => b.vp - a.vp;
}

export function calculateResults(
    round: Round,
    _: RoundData
): Map<TableNumber, TableRoundResult> {

    function getKey(ns: PairNumber, ew: PairNumber): string {
        return ns.toString() + "_" + ew.toString();
    }

    const resultsByPlayers = new Map<string, TableRoundResult>();

    const tables = Object.entries(round.rotation);

    for (const [key, table] of tables) {
        const tablenum = Number.parseInt(key);
        const matchResult: TableRoundResult = {
            round: round.number,
            table: tablenum,
            status: "not-played",
            ns: table.ns,
            ew: table.ew,
        };
        resultsByPlayers.set(getKey(table.ns, table.ew), matchResult);
    }

    round.boardResults
        ?.filter((r) => r.status === "played" || r.status === "adjusted")
        .map((r) => r as CompleteBoardResult)
        .forEach((boardResult) => {
            let matchResult = resultsByPlayers.get(getKey(boardResult.ns, boardResult.ew));
            if (!matchResult) {
                console.warn(`Result not found: Round: ${round.number}, NS: ${boardResult.ns} EW: ${boardResult.ew}`);
                return;
            }
            if (matchResult.status !== "played") {
                matchResult = {
                    ...matchResult,
                    status: "played",
                    imp_ew: 0,
                    imp_ns: 0,
                    vp_ew: 0,
                    vp_ns: 0,
                };
                resultsByPlayers.set(getKey(matchResult.ns, matchResult.ew), matchResult);
            }

            if (boardResult.status === "played") {
                let res_ns = boardResult.ns === matchResult.ns ? boardResult.res_ns : boardResult.res_ew;
                if (boardResult.rotated) res_ns *= -1;

                if (res_ns > 0)
                    matchResult.imp_ns += res_ns;
                else matchResult.imp_ew -= res_ns;
            }
            else if (boardResult.status === "adjusted") {
                if (boardResult.res_ns > 0)
                    matchResult.imp_ns += boardResult.res_ns;
                else matchResult.imp_ew -= boardResult.res_ns;
                
                if (boardResult.res_ew > 0)
                    matchResult.imp_ew += boardResult.res_ew;
                else matchResult.imp_ns -= boardResult.res_ew;
            }
        });

    const resultsByTable = new Map<TableNumber, TableRoundResult>();

    for (const [key, table] of tables) {
        const tablenum = Number.parseInt(key);
        resultsByTable.set(tablenum, resultsByPlayers.get(getKey(table.ns, table.ew))!);
    }


    Array.from(resultsByPlayers.values()).filter((r) => r.status === "played").forEach((r) => {
        const result = r as PlayedMatchResult;
        const vp = calculateVP(result.imp_ns - result.imp_ew);
        result.vp_ns = vp.ns;
        result.vp_ew = vp.ew;
    });

    return resultsByTable;
}

export function calculateAllPairResult(
    rounds: Round[],
    tournament: Tournament
): Map<PairNumber, PairSumResult> {

    if (rounds.length === 0) return new Map<PairNumber, PairSumResult>();

    const pairs = Object.values(rounds[0]!.rotation).flatMap((t) => [t.ns, t.ew]).filter((p) => p !== 0);

    const allAdjusts = rounds.flatMap((r) => r.adjusts);

    const pairResults = pairs.map((pair) => {

        const results = tournament.getPairRoundResults(pair, rounds);

        const pairAdjusts = allAdjusts.filter((a) => a.participant === pair);

        const vps =  results.reduce((acc, curr) => acc + (curr.vps ?? 0), 0)
        const adjustsVps = pairAdjusts.reduce((acc, curr) => acc + curr.vpAdjustment, 0);
        const totalVps = vps + adjustsVps;

        return new PairSumResult({
            pair: pair,
            vp: totalVps,
            rank: Rank.default(tournament.getPairGroup(pair)?.players.length),
            matchResults: results,
            adjusts: pairAdjusts,
        })
    });

    // RANK Calculation

    const res = new Map<PairNumber, PairSumResult>();
    pairResults.forEach((r) => res.set(r.pair, r));

    const resultComparator = createPairSumResultComparator(tournament);

    tournament.groups.forEach((g) => {
        const results = g.players.map((p) => res.get(p)!) as PairSumResult[];
        results.sort(resultComparator);

        const counts = results.reduce((acc, curr) => {
            if (acc.has(curr.vp)) {
                acc.set(curr.vp, acc.get(curr.vp)! + 1);
            } else {
                acc.set(curr.vp, 1);
            }
            return acc;
        }, new Map<number, number>());

        let rank = 1;
        let rankCumm = 0;

        for (let i = 0; i < results.length; i++) {
            if (i !== 0 && results[i]!.vp !== results[i - 1]!.vp) {
                rank += rankCumm + 1;
                rankCumm = 0;
            } else if (i !== 0) {
                rankCumm++;
            }

            results[i]!.rank = new Rank(rank, counts.get(results[i]!.vp)!);
        }
    });

    return res;
}