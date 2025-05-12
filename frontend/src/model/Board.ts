




export interface Board {
    vul: "None" | "NS" | "EW" | "All" | string;
    dealer: "N" | "S" | "E" | "W" | string;
    deal: [string, string, string, string],
    ability?: BoardAnalysis;
    minimax?: string;
}

// N n s h d c E e w n s h d c S s e w n s h d c W w n s h d c
export type BoardAnalysis = number[];

export type PositionString = "N" | "S" | "E" | "W";

export type BoardNumber = number;
export type BoardNumberKey = string;


