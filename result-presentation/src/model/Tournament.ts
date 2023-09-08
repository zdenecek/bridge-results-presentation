import { throwError } from "@/utils/error";
import { Round, RoundData } from "./Round";
import {
    Group,
    Pair,
    PairNumber,
    PairNumberKey,
    RoundNumber,
    RoundNumberKey,
    RoundRotation,
    TableNumber,
} from "./modelTypes";
import { PairSumResult, PairTableRoundResult, TableRoundResult } from "./MatchResult";
import { calculateAllPairResult } from "./ResultsCalculation";

export type TournamentData = {
    title: string;
    totalRounds: number;
    td?: {
        name: string;
        email?: string;
        phone?: string;
        website?: string;
    };

    groups: Group[];
    players: Record<PairNumberKey, Pair>;
    rotations: Record<RoundNumberKey, RoundRotation>;
    rounds: Record<RoundNumberKey, RoundData>;
};

export class Tournament {
    title!: string;
    td?: {
        name: string;
        email?: string;
        phone?: string;
        website?: string;
    };
    totalRounds!: number;

    groups!: Group[];
    players!: Record<PairNumberKey, Pair>;
    rotations!: Record<RoundNumberKey, RoundRotation>;
    rounds: Record<RoundNumberKey, Round>;

    constructor(tournamentData: TournamentData) {
        Object.assign(this, tournamentData);

        this.rounds = Object.fromEntries(
            Object.entries(tournamentData.rounds).map(
                ([key, r]) =>
                    [key, new Round(r, this.rotations[key])] as [
                        RoundNumberKey,
                        Round
                    ]
            )
        );
        this.pairResults = new Map<RoundNumber, Map<PairNumber, PairSumResult>|undefined>();
        Array.from(Array(this.totalRounds).keys()).forEach((r) => {
            this.pairResults.set(r, undefined);
        });
    }

    private pairResults: Map<RoundNumber, Map<PairNumber, PairSumResult> | undefined>;

    public get isFinished(): boolean {
        return this.standing === this.totalRounds;
    }

    getSeating(
        round: RoundNumber,
        table: TableNumber
    ): { ns: PairNumber; ew: PairNumber } {
        return this.rotations[round.toString()][table.toString()];
    }

    getPairGroup(pair: PairNumber): Group {
        return (
            this.groups.find((g) => g.players.includes(pair)) ??
            throwError(`Pair ${pair} not found in any group`)
        );
    }

    getPairResult(pair: PairNumber, untilRound?: RoundNumber): PairSumResult | undefined {
        let round = untilRound ?? this.standing;


        if(round > this.standing) round = this.standing;
        if(this.pairResults.get(round - 1) === undefined) 
            this.pairResults.set(round - 1, calculateAllPairResult( Object.values(this.rounds).slice(0, round ) ,this));

        return this.pairResults.get(round - 1)!.get(pair) ?? undefined;
    }

    getPairResults(untilRound?: RoundNumber): Map<PairNumber,PairSumResult> {
        let round = untilRound ?? this.standing;
        if(round > this.standing) round = this.standing;    
        if(this.pairResults.get(round - 1) === undefined)
            this.pairResults.set(round - 1, calculateAllPairResult( Object.values(this.rounds).slice(0, round ) ,this));

        return this.pairResults.get(round - 1)!;
    }


    getPairRoundResult(
        pair: PairNumber,
        round: RoundNumber
    ): PairTableRoundResult | undefined {
        return this.rounds[round.toString()]?.getPairResult(pair);
    }

    getPairRoundResults(pair: PairNumber, rounds: Round[] | undefined = undefined): PairTableRoundResult[] {
        const results = Object.values(rounds ?? this.rounds).map(
            (r) => r.getPairResult(pair)
        ).filter((r) => r !== undefined) as PairTableRoundResult[];
        return results ;
}

    getRoundResults(round: RoundNumber): TableRoundResult[] {
        const items =  this.rounds[round.toString()]?.getMatchResults().values();
        return Array.from(
           items ?? []
        );
    }

    getPair(number: PairNumber | PairNumberKey): Pair | undefined {
        return this.players[
            typeof number === "string" ? number.toString() : number
        ];
    }

    get standing(): RoundNumber {
        return Object.values(this.rounds)
            .map((r) => r.number)
            .reduce((a, b) => Math.max(a, b), 0);
    }
}
