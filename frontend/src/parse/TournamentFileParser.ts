// @ts-nocheck
import { BoardNumberKey } from "@/model/Board";
import { BoardResult } from "@/model/BoardResult";
import { RoundData } from "@/model/Round";
import { TournamentData } from "@/model/Tournament";

export default class TournamentFileParser {
    static async applyFile(
        file: File,
        data: TournamentData,
        round: number
    ): Promise<TournamentData> {
        const text = await file.text();

        if (data.rounds[round] === undefined)
            data.rounds[round] = this.createRoundData(round);

        if (file.name.includes("Opis_Rozdani_Excel"))
            return this.applyResultsFile(text, data, round);
        else if (file.name.endsWith(".pbn"))
            return this.applyDealsFile(text, data, round);
        else if (file.name.includes("Opis_Rozdani") || file.name.includes("OpisRozdani"))
            return this.applyAveragesFile(text,data, round);

        console.error("Unknown file type: " + file.name);
        return data;
    }

    static createRoundData(number: number): RoundData {
        return {
            boardResults: [],
            date: new Date().toISOString().slice(0, 10),
            boards: {},
            overwrites: [],
        };
    }

      static createResultFromLine(values: string[], rotations: RoundRotation): BoardResult  | null {
            /* Opis rozdani Excel

            0   Cislo_rozdania 
            1   Table

            2   Zavazek
            3   Vysledek
            4   HH 

            5   hodnota
            6   T / N (hráno / nehráno) 
            7   Mezera
            8   +/- Imp
            */

            const table = Number.parseInt(values[1]);
            const boardNumber = Number.parseInt(values[0]);
            // Create base result with table and deal info
            const baseResult = {
                deal: boardNumber,
                table: table,
                ns: rotations[table]?.ns,
                ew: rotations[table]?.ew,
            };

            // Handle not played case

            if (values[6] === "N") {
                // not played
                return {
                    ...baseResult,
                    status: "not-played",
                };
            }

            // Create played result
            const res = Number.parseInt(values[8]);
            let output = {
                ...baseResult,
                status: "played" as "played",
                contract: values[2],
                declarer: values[4],
                result: values[3],
                points: Number.parseInt(values[5]),
                res_ns: res,
                res_ew: -res,
            };

            if (values[7] === "*") {
                output = Object.assign(output, { rotated: true });
            }

            if (table == 15) {
                console.log(output);
            }

            return output;
        }

    static applyResultsFile(
        text: string,
        data: TournamentData,
        round: number
    ): TournamentData {
        const rotations = data.rotations[round];
        const tables = Object.keys(rotations);
        const results = [] as BoardResult[];
        const lines = text.replaceAll(`"`, "").split("\r\n");
        const roundObj = data.rounds[round];

        for (const line of lines) {
            const values = line.split(",");
            const result = TournamentFileParser.createResultFromLine(values, rotations);
            if (result) results.push(result);
        }

        console.debug("Applying results file");
        roundObj.boardResults = results;

        return data;
    }

    static applyDealsFile(
        text: string,
        data: TournamentData,
        round: number
    ): TournamentData {

        const roundObj = data.rounds[round];
        if (!roundObj) throw new Error("Round " + round + " not found");

        const boards = {};

        let state = false;
        let ability_present = false;
        let dealer = "";
        let vul = "";
        let deal = [] as string[];
        let ability = [] as number[];
        let minimax = "";
        let boardNum = "";

        function save() {

            if(deal.length !== 4) return;

            const board = {
                dealer: dealer,
                vul: vul,
                deal: deal,
            };
            if (ability_present)
                Object.assign(board, { ability: ability, minimax: minimax });



            Object.assign(boards, { [boardNum]: board });
        }

        const lines = text.split("\r\n");
        for (const line of lines) {
            const t = line.split('"')[1];
            if (!t) throw new Error("Malformed pbn line: " + line);
            if (line.startsWith("[Board")) {
                if (state) save();

                state = true;
                boardNum = t
            } else if (line.startsWith("[Dealer")) {
                dealer = t
            } else if (line.startsWith("[Vulnerable")) {
                vul = t
            } else if (line.startsWith("[Deal")) {
                deal = t.slice(2).split(" ");
            } else if (line.startsWith("[Ability")) {
                ability_present = true;
                // example 'N:43759 E:8A684 S:43749 W:8A684'
                const abilityStrs = [
                    ...t.slice(2, 7).split(""),
                    ...t.slice(10, 15).split(""),
                    ...t.slice(18, 23).split(""),
                    ...t.slice(26, 31).split(""),
                ];

                ability = abilityStrs.map((s) =>
                    Number.parseInt(s, 16)
                );
            } else if (line.startsWith("[Minimax")) {
                minimax = line.split('"')[1] as string;
            }

            save();

        }

        roundObj.boards = boards;

        return data;

    }


    static applyAveragesFile(
        text: string,
        data: TournamentData,
        round: number
    ): TournamentData {

        const averages = {} as Record<BoardNumberKey, Number>;

        const lines = text.split("\r\n");

        // Rozdani c. 1     Prumer:   20     Rozdani c. 2     Prumer:  150     Rozdani c. 3     Prumer:  450 
      
        lines.filter(line => line.startsWith("Rozdani c."))
        .forEach(line => line
        .split("Rozdani c.")
        .map(s => s.trim())
        .filter(s => s.length > 0)
        .map(s => s.split("Prumer:").map(s => s.trim()))
        .forEach(([num, average]) => {
            if (!average || !num) throw new Error("Average is undefined for " + num);
            averages[num] = Number.parseInt(average);
        }));

        Object.assign(data.rounds[round], { averages })

        return data;
    }
}
