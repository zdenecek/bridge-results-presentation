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
import { PostponedMatchData, PostponedSession, PostponedSessionData } from "./PostponedSession";
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
  postponedSessions?: PostponedSessionData[];
};

export type UpcomingMatch = {
  round: RoundNumber;
  table: TableNumber;
  date?: Date;
  opponent: PairNumber;
  ns: boolean;
  /** A postponed match of a round already played. */
  postponed: boolean;
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
  postponedSessions: Map<string, PostponedSession>;
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

    this.postponedSessions = new Map(
      (tournamentData.postponedSessions ?? []).map((s) => [s.id, new PostponedSession(s)])
    );

    this.pairResults = new Map<
      RoundNumber,
      Map<PairNumber, PairSumResult> | undefined
    >();
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
    return (
      this.getPairResults(untilRound).get(pair) ??
      PairSumResult.Default(pair, this.getPairGroup(pair)?.players.length)
    );
  }

  /** Keyed by the rounds included: a match played ahead of its round counts only with all rounds. */
  getPairResults(untilRound?: RoundNumber): Map<PairNumber, PairSumResult> {
    const key = untilRound ?? Number.POSITIVE_INFINITY;
    if (this.pairResults.get(key) === undefined)
      this.pairResults.set(
        key,
        calculateAllPairResult(this.getRoundsUntil(untilRound), this)
      );

    return this.pairResults.get(key)!;
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
    if (rounds) return rounds.flatMap((r) => r.getPairResults(pair));
    // Rounds not played yet only count with matches played ahead of them.
    return this.getRoundsUntil()
      .flatMap((r) => r.getPairResults(pair))
      .filter((r) => r.round <= this.standing || r.status !== "not-played");
  }

  /** Future rounds and pending postponed matches, in round order. */
  getPairUpcomingMatches(pair: PairNumber): UpcomingMatch[] {
    return Array.from(this.rotations.entries())
      .sort(([a], [b]) => a - b)
      .flatMap(([roundNumber, rotation]) =>
        Object.entries(rotation)
          .filter(([, seating]) => seating.ns === pair || seating.ew === pair)
          .flatMap(([tableKey, seating]) => {
            const table = Number.parseInt(tableKey);
            const round = this.getRound(roundNumber);
            const result = round?.getTableResult(table);
            if (result && result.status !== "not-played") return [];
            const postponed = roundNumber <= this.standing;
            if (postponed && !round?.isTablePending(table)) return [];
            return [{
              round: roundNumber,
              table,
              date: postponed ? round?.getPostponedDate(table) : round?.date,
              opponent: seating.ns === pair ? seating.ew : seating.ns,
              ns: seating.ns === pair,
              postponed,
            }];
          })
      );
  }

  getRoundResults(round: RoundNumber): TableRoundResult[] {
    const items = this.getRound(round)?.getMatchResults().values();
    return Array.from(items ?? []);
  }

  getPair(number: PairNumber): Pair | undefined {
    return this.players.get(number);
  }

  /** Pairs as they actually sat at the table of a postponed match. */
  getPostponedSeating(match: PostponedMatchData): { ns?: PairNumber; ew?: PairNumber } {
    const seating = this.rotations.get(match.round)?.[match.table.toString()];
    if (!seating) return {};
    return match.swapped ? { ns: seating.ew, ew: seating.ns } : { ns: seating.ns, ew: seating.ew };
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
