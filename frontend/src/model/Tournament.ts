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
import {
    PairSumResult,
    PairTableRoundResult,
    TableRoundResult,
} from "./MatchResult";
import { calculateAllPairResult } from "./ResultsCalculation";
import { TournamentType } from "./TournamentType";

export type TournamentData = {
    type?: TournamentType;
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
    type: TournamentType;
    title: string;
    td?: {
        name: string;
        email?: string;
        phone?: string;
        website?: string;
    };
    totalRounds: number;

    groups: Group[];
    players: Map<PairNumber, Pair>;
    rotations: Map<RoundNumber, RoundRotation>;
    rounds: Record<RoundNumberKey, Round>;

    protected pairResults: Map<
        RoundNumber,
        Map<PairNumber, PairSumResult> | undefined
    >;

    constructor(tournamentData: TournamentData) {
        this.type = tournamentData.type ?? TournamentType.BKP_SKUPINOVKA;
        this.title = tournamentData.title;
        this.td = tournamentData.td;
        this.totalRounds = tournamentData.totalRounds;
        this.groups = tournamentData.groups;

        this.players = new Map();
        Object.values(tournamentData.players).forEach((p) => {
            this.players.set(p.id, p);
        });

        this.rotations = new Map();
        Object.entries(tournamentData.rotations).forEach(([key, r]) => {
            this.rotations.set(Number.parseInt(key), r);
        });

        this.rounds = Object.fromEntries(
            Object.entries(tournamentData.rounds).map(
                ([key, r]) =>
                    [
                        key,
                        new Round(r, this.rotations.get(Number.parseInt(key))!),
                    ] as [RoundNumberKey, Round]
            )
        );
        this.pairResults = new Map<
            RoundNumber,
            Map<PairNumber, PairSumResult> | undefined
        >();
        Array.from(Array(this.totalRounds).keys()).forEach((r) => {
            this.pairResults.set(r, undefined);
        });
    }

    public get isFinished(): boolean {
        return this.standing === this.totalRounds;
    }

    public getRoundGroupSeatings() {}

    getTableSeating(
        round: RoundNumber,
        table: TableNumber
    ): { ns: PairNumber; ew: PairNumber } {
        return (
            this.rotations.get(round)?.[table.toString()] ??
            throwError(`Table ${table} not found in round ${round}`)
        );
    }

    getPairGroup(pair: PairNumber): Group | undefined {
        if (this.players.get(pair)?.isBye) return undefined;
        return (
            this.groups.find((g) => g.players.includes(pair)) ??
            throwError(`Pair ${pair} not found in any group`)
        );
    }

    getPairResult(
        pair: PairNumber,
        untilRound?: RoundNumber
    ): PairSumResult  {
        let round = untilRound ?? this.standing;

        if (round > this.standing) round = this.standing;
        if (this.pairResults.get(round - 1) === undefined)
            this.pairResults.set(
                round - 1,
                calculateAllPairResult(
                    Object.values(this.rounds).slice(0, round),
                    this
                )
            );

        return this.pairResults.get(round - 1)!.get(pair) ?? PairSumResult.Default(pair, this.getPairGroup(pair)?.players.length );
    }

    getPairResults(untilRound?: RoundNumber): Map<PairNumber, PairSumResult> {
        let round = untilRound ?? this.standing;
        if (round > this.standing) round = this.standing;
        if (this.pairResults.get(round - 1) === undefined)
            this.pairResults.set(
                round - 1,
                calculateAllPairResult(
                    Object.values(this.rounds).slice(0, round),
                    this
                )
            );

        return this.pairResults.get(round - 1)!;
    }

    getPairRoundResult(
        pair: PairNumber,
        round: RoundNumber
    ): PairTableRoundResult | undefined {
        return this.rounds[round.toString()]?.getPairResult(pair);
    }

    getPairRoundResults(
        pair: PairNumber,
        rounds: Round[] | undefined = undefined
    ): PairTableRoundResult[] {
        const results = Object.values(rounds ?? this.rounds)
            .map((r) => r.getPairResult(pair))
            .filter((r) => r !== undefined) as PairTableRoundResult[];
        return results;
    }

    getRoundResults(round: RoundNumber): TableRoundResult[] {
        const items = this.rounds[round.toString()]?.getMatchResults().values();
        return Array.from(items ?? []);
    }

    getPair(number: PairNumber): Pair | undefined {
        return this.players.get(number);
    }

    get standing(): RoundNumber {
        return Object.values(this.rounds)
            .map((r) => r.number)
            .reduce((a, b) => Math.max(a, b), 0);
    }

    public wasRoundPlayed(round: RoundNumber): boolean {
        return (this.rounds[round.toString()]?.boardResults.length ?? 0) > 0;
    }
}
