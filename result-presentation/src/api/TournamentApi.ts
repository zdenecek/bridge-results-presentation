import { Tournament } from "../model/Tournament";

import axios from "axios";

export default class TournamentApi {
    public static getTournament(id: string): Promise<Tournament> {
        return axios.get(process.env.VUE_APP_API_URL + id).then((response) => {
            const tournament = new Tournament(response.data);
            console.debug(tournament.getPairRoundResult(11,4))
            return tournament;
        });
    }
}
