
export interface Pair {
    title: string;
    id: number;
    isBye?: boolean; 
    players: Player[];
}
export interface Player {
    id: string;
    name: string;
    club: string;
}

export interface Group {
    name: string;
    players: PairNumber[];
}

export type TableNumber = number;
export type TableNumberKey = string;
export type PairNumber = number;
export type PairNumberKey = string;
export type RoundNumber = number;
export type RoundNumberKey = string;

export type SegmentNumber = number;
export type SegmentNumberKey = string;


export type RoundRotation = Record<
    TableNumberKey,
    {
        ns: PairNumber;
        ew: PairNumber;
    }
>;

export type SegmentedRoundRotation = Record<SegmentNumberKey, RoundRotation>;





