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
import {
  calculateAllPairResult,
} from "./ResultsCalculation";
import { TournamentType } from "./TournamentType";
import _ from "lodash";

export type TournamentData = {
  type?: TournamentType;
  settings?: {
    rankByAverage?: boolean;
  };
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
  rounds: Map<RoundNumber, Round>;
  settings: {
    rankByAverage: boolean;
  };

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

    this.settings = {
      rankByAverage: tournamentData.settings?.rankByAverage ?? false,
    };

    this.players = new Map();
    Object.values(tournamentData.players).forEach((p) => {
      this.players.set(p.id, p);
    });

    this.rotations = new Map();
    Object.entries(tournamentData.rotations).forEach(([key, r]) => {
      this.rotations.set(Number.parseInt(key), r);
    });

    this.rounds = new Map();
    Object.entries(tournamentData.rounds).forEach(([key, roundData]) => {
      const roundNumber = Number.parseInt(key);
      const roundRotation = this.rotations.get(Number.parseInt(key))!;
      this.rounds.set(
        roundNumber,
        new Round(roundData, roundNumber, roundRotation)
      );
    });

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

  public getRound(round: RoundNumber): Round | undefined {
    return this.rounds.get(round);
  }

  getTableSeating(
    round: RoundNumber,
    table: TableNumber
  ): { ns: PairNumber; ew: PairNumber; postponed?: boolean } {
    const res =
      this.rotations.get(round)?.[table.toString()] ??
      throwError(`Table ${table} not found in round ${round}`);

    const postponed = this.getRound(round)?.isTablePostponed(table) ?? false;

    return {
      ns: res.ns,
      ew: res.ew,
      postponed,
    };
  }

  getPairGroup(pair: PairNumber): Group | undefined {
    if (this.players.get(pair)?.isBye) return undefined;
    return this.groups.find((g) => g.players.includes(pair));
  }

  getRoundsUntil(untilRound?: RoundNumber): Round[] {
    if (untilRound === undefined) return Array.from(this.rounds.values());

    return Array.from(this.rounds.entries())
      .filter(([key]) => untilRound === undefined || key <= untilRound)
      .map(([_, value]) => value);
  }

  getPairResult(pair: PairNumber, untilRound?: RoundNumber): PairSumResult {
    let round = untilRound ?? this.standing;

    if (round > this.standing) round = this.standing;
    if (this.pairResults.get(round - 1) === undefined)
      this.pairResults.set(
        round - 1,
        calculateAllPairResult(this.getRoundsUntil(untilRound), this)
      );

    return (
      this.pairResults.get(round - 1)!.get(pair) ??
      PairSumResult.Default(pair, this.getPairGroup(pair)?.players.length)
    );
  }

  getPairResults(untilRound?: RoundNumber): Map<PairNumber, PairSumResult> {
    let round = untilRound ?? this.standing;
    if (round > this.standing) round = this.standing;
    if (this.pairResults.get(round - 1) === undefined)
      this.pairResults.set(
        round - 1,
        calculateAllPairResult(this.getRoundsUntil(untilRound), this)
      );

    return this.pairResults.get(round - 1)!;
  }

  getPairRoundResult(
    pair: PairNumber,
    round: RoundNumber
  ): PairTableRoundResult[] | undefined {
    return this.getRound(round)?.getPairResults(pair);
  }

  getPairRoundResults(
    pair: PairNumber,
    rounds: Round[] | undefined = undefined
  ): PairTableRoundResult[] {
    const roundsToCheck = rounds ?? this.getRoundsUntil(this.standing);
    console.log("hi")
    const results = roundsToCheck
      .flatMap((r) => r.getPairResults(pair))
      .filter((r) => r !== undefined) as PairTableRoundResult[];
    return results;
  }

  getRoundResults(round: RoundNumber): TableRoundResult[] {
    const items = this.getRound(round)?.getMatchResults().values();
    return Array.from(items ?? []);
  }

  getPair(number: PairNumber): Pair | undefined {
    return this.players.get(number);
  }

  get standing(): RoundNumber {
    const standing = Array.from(this.rounds.values())
      .filter((r) => this.wasRoundPlayed(r.number))
      .map((r) => r.number)
      .reduce((a, b) => Math.max(a, b), 0);
    return standing;
  }

  public wasRoundPlayed(round: RoundNumber): boolean {
    return (this.getRound(round)?.boardResults?.length ?? 0) > 0;
  }

  public toMatrikaString(): string {
    const results = Array.from(this.getPairResults().values())
      .filter((res) => !this.getPair(res.pair)?.isBye)
      .map((result) => ({
        result,
        rankOrdinal: result.rank.toOrdinal(),
        groupOrdinal: Number.parseInt(
          this.getPairGroup(result.pair)!.name.charAt(1)
        ),
      }));

    results.sort((res, res2) => res.rankOrdinal - res2.rankOrdinal);
    results.sort((res, res2) => res.groupOrdinal - res2.groupOrdinal);

    let count = 0;
    let rank = 1;
    let lastRankOrdinal = 1;
    let lastGroupOrdinal = 1;
    return results
      .map((res) => {
        const pair = this.getPair(res.result.pair)!;

        if (
          res.rankOrdinal !== lastRankOrdinal ||
          res.groupOrdinal !== lastGroupOrdinal
        ) {
          rank += count;
          lastRankOrdinal = res.rankOrdinal;
          lastGroupOrdinal = res.groupOrdinal;
          count = 0;
        }

        count++;

        return [
          rank, // rank
          this.settings.rankByAverage
            ? res.result.average?.toFixed(2)
            : res.result.vp, // result
          pair.players.some((p) => !p.id) ? pair.title : "", // if some player doesnt have id, use a title, else empty string
          ...(pair?.players.map((p) => p.id).filter((p) => p) ?? []), // ids
        ];
      })
      .map((row) => row.join(","))
      .join("\n");
  }
}
