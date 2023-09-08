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
            number: number,
            boards: {},
            overwrites: [],
        };
    }

    static applyResultsFile(
        text: string,
        data: TournamentData,
        round: number
    ): TournamentData {
        const rotations = data.rotations[round];
        const tables = Object.keys(rotations);

        function createResult(values: string[]): BoardResult | null {
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

            const table = values[1];
            if (tables.includes(table) === false) return null;
            const rotation = rotations[table];

            const boardNumber = Number.parseInt(values[0]);

            if (values[6] === "N")
                // not played
                return {
                    deal: boardNumber,
                    ns: rotation["ns"],
                    ew: rotation["ew"],
                    status: "not-played",
                };

            const res = Number.parseInt(values[8]);

            let output = {
                status: "played",
                deal: boardNumber,
                ns: rotation["ns"],
                ew: rotation["ew"],
                contract: values[2],
                declarer: values[4],
                result: values[3],
                points: Number.parseInt(values[5]),
                res_ns: res,
                res_ew: -res,
            };

            if (values[7] === "*")
                output = Object.assign(output, { rotated: true });

            return output;
        }

        const results = [] as BoardResult[];
        const lines = text.replaceAll(`"`, "").split("\r\n");

        for (const line of lines) {
            const values = line.split(",");
            const result = createResult(values);
            if (result) results.push(result);
        }

        console.debug("Applying results file");
        data.rounds[round].boardResults = results;

        return data;
    }

    static applyDealsFile(
        text: string,
        data: TournamentData,
        round: number
    ): TournamentData {
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
            const board = {
                dealer: dealer,
                vul: vul,
                deal: deal,
            };
            if (ability_present)
                Object.assign(board, { ability: ability, minimax: minimax });

            Object.assign(boards, { board: board });
        }

        const lines = text.split("\r\n");
        for (const line of lines) {
            if (line.startsWith("[Board")) {
                if (state) save();

                state = true;
                boardNum = line.split('"')[1];
            } else if (line.startsWith("[Dealer")) {
                dealer = line.split('"')[1];
            } else if (line.startsWith("[Vulnerable")) {
                vul = line.split('"')[1];
            } else if (line.startsWith("[Deal")) {
                deal = line.split('"')[1].slice(2).split(" ");
            } else if (line.startsWith("[Ability")) {
                ability_present = true;
                // example 'N:43759 E:8A684 S:43749 W:8A684'
                const t = line.split('"')[1];
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
                minimax = line.split('"')[1];
            }

            save();

        }

        data.rounds[round].boards = boards;

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
        .forEach( ([num, average]) => Object.assign(averages, { [num]: Number.parseInt(average)}) ));

        Object.assign(data.rounds[round], { averages })

        return data;
    }
}
