import { CompleteBoardResult } from "./BoardResult";
import { MatchResult, PairSumResult, PairTableRoundResult, PlayedMatchResult, TableRoundResult } from "./MatchResult";
import { Round, RoundData } from "./Round";
import { PairNumber, PairNumberKey, TableNumber } from "./modelTypes";
import { calculateVP } from "./VP";
import { Rank } from "./Rank";
import { Tournament } from "./Tournament";

export function calculateResults(
    round: Round,
    data: RoundData
):  Map<TableNumber, TableRoundResult> {
    
    const resultsByNS = new Map<TableNumber, TableRoundResult>();

    const tables = Object.entries(round.rotation);

    for (const [key, table] of tables) {
        const tablenum =  Number.parseInt(key);
        const matchResult: TableRoundResult = {
            round: round.number,
            table:tablenum,
            status: "not-played",
            ns: table.ns,
            ew: table.ew,
        };
        resultsByNS.set(table.ns, matchResult);
    }

   round.boardResults
        ?.filter((r) => r.status === "played")
        .map((r) => r as CompleteBoardResult)
        .forEach((result) => {
            let r = resultsByNS.get(result.ns) || resultsByNS.get(result.ew);
            if(!r) {
                console.warn(`Result not found: Round: ${round.number}, NS: ${result.ns} EW: ${result.ew}`);
                return;
            }
            if (r.status !== "played") {
                r = {
                    ...r,
                    status: "played",
                    imp_ew: 0,
                    imp_ns: 0,
                    vp_ew: 0,
                    vp_ns: 0,
                };
                resultsByNS.set(r.ns, r);
            }

            let res_ns = result.ns === r.ns ? result.res_ns : result.res_ew;
            if(result.rotated) res_ns *= -1;

            if( res_ns > 0 ) 
                r.imp_ns += res_ns;
            else r.imp_ew -= res_ns;
        });

    const resultsByTable = new Map<TableNumber, TableRoundResult>();

    for (const [key, table] of tables) {
        const tablenum =  Number.parseInt(key);
        resultsByTable.set(tablenum, resultsByNS.get(table.ns)!);
    }


    Array.from(resultsByNS.values()).filter((r) => r.status === "played").forEach((r) => {
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
    
    if(rounds.length === 0) return new Map<PairNumber, PairSumResult>();

    const pairs = Object.values(rounds[0].rotation).flatMap((t) => [t.ns, t.ew]).filter((p) => p !== 0);

    const pairResults = pairs.map((pair) => { 
        
        const results =  tournament.getPairRoundResults(pair, rounds);
        
        return new PairSumResult({
        pair: pair,
        vp: results.reduce((acc, curr) => acc + (curr.vps ?? 0), 0),
        rank: Rank.default(tournament.getPairGroup(pair)?.players.length),
        matchResults: results
    }) });

    // RANK Calculation

    const res = new Map<PairNumber, PairSumResult>();
    pairResults.forEach((r) => res.set(r.pair, r));

    tournament.groups.forEach((g) => {
        const results = g.players.map((p) => res.get(p)!);
        results.sort((a, b) => b.vp - a.vp);

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
            if (i !== 0 && results[i].vp !== results[i - 1].vp) {
                rank += rankCumm + 1;
                rankCumm = 0;
            } else if (i !== 0) {
                rankCumm++;
            }

            results[i].rank = new Rank(rank, counts.get(results[i].vp)!);
        }
    });

    return res;
}