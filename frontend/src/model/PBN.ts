import { Round } from "./Round";


export function createPBN(round: Round): string {
    
    let str = `
% PBN 1.0
[Generator "Prezentace Zdenek Tomis 0.2.0"]
`
    if(!round.boards) return str;

    for (const [num, board] of round.boards.entries()) {
        str += `
[Board "${num}"]
[Dealer "${board.dealer}"]
[Vulnerable "${board.vul}"]
[Deal "N:${board.deal.join(' ')}"]
`;

        if(board.ability) {
            const strs = [0,1,2,3].map((i) => 
                board.ability?.slice(i*4, i*4+5).map((a) => a.toString(16).toUpperCase ()).join(''))

            str += `[Ability "N:${strs[0]} E:${strs[1]} S:${strs[2]} W:${strs[3]}"]\n`
        }
        if (board.minimax) {
            str += `[Minimax "${board.minimax}"]\n`
        }

    }
    return str;

}