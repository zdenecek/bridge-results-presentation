import { Tournament, TournamentData } from "./model/Tournament";

export function getData(): Tournament {
    throw new Error("Not implemented");
    // return new Tournament(testdata as TournamentData);
}

const testdata = {
        "type": "bkp-swiss",
		"title": "Švýcarská skupinovka B Podzim 2023",
		"totalRounds": 1,
		"groups": [
			{
				"name": "1",
				"players": [
					1,
					2,
					3,
					4,
					5,
					6,
					7,
					8
				]
			},
			{
				"name": "2",
				"players": [
					21,
					22,
					23,
					24,
					25,
					26,
					27,
					28
				]
			}
		],
		"rotations": {
			"1": {
				"1": {
					"ns": 10,
					"ew": 1
				},
				"2": {
					"ns": 9,
					"ew": 8
				},
				"3": {
					"ns": 7,
					"ew": 3
				},
				"4": {
					"ns": 5,
					"ew": 2
				},
				"5": {
					"ns": 4,
					"ew": 6
				},
				"6": {
					"ns": 20,
					"ew": 11
				},
				"7": {
					"ns": 19,
					"ew": 18
				},
				"8": {
					"ns": 17,
					"ew": 13
				},
				"9": {
					"ns": 15,
					"ew": 12
				},
				"10": {
					"ns": 14,
					"ew": 16
				},
				"11": {
					"ns": 21,
					"ew": 28
				},
				"12": {
					"ns": 0,
					"ew": 0
				},
				"13": {
					"ns": 27,
					"ew": 22
				},
				"14": {
					"ns": 23,
					"ew": 26
				},
				"15": {
					"ns": 25,
					"ew": 24
				}
			}
		},
		"players": {
            "1": {
                "id": 1,
                "title": "Filip - Ruman\u010d\u00edk",
                "players": [
                    { "id": "1502", "name": "Filip Anatol", "club": "BKP" },
                    {
                        "id": "2943",
                        "name": "Ruman\u010d\u00edk Jakub",
                        "club": "HAV"
                    }
                ]
            },
            "2": {
                "id": 2,
                "title": "Hol\u00fd - Nul\u00ed\u010dek",
                "players": [
                    { "id": "892", "name": "Hol\u00fd Jaroslav", "club": "BKP" },
                    { "id": "1188   ", "name": "", "club": "" }
                ]
            },
            "3": {
                "id": 3,
                "title": "Vajdovi",
                "players": [
                    {
                        "id": "2765",
                        "name": "Vajda Tom\u00e1\u0161",
                        "club": "CHB"
                    },
                    {
                        "id": "2062",
                        "name": "Vajdov\u00e1 Pavl\u00edna",
                        "club": "CHB"
                    }
                ]
            },
            "4": {
                "id": 4,
                "title": "Franz - Kr\u00e1sa",
                "players": [
                    { "id": "3559", "name": "Franz Vojt\u011bch", "club": "BKP" },
                    {
                        "id": "3236",
                        "name": "Kr\u00e1sa Mikul\u00e1\u0161",
                        "club": "BKP"
                    }
                ]
            },
            "5": {
                "id": 5,
                "title": "Svobodovi",
                "players": [
                    { "id": "341", "name": "Svoboda Otakar", "club": "BKP" },
                    { "id": "342", "name": "Svobodov\u00e1 Pavla", "club": "BKP" }
                ]
            },
            "6": {
                "id": 6,
                "title": "St\u00e1dn\u00edk - Pl\u00e1ni\u010dka",
                "players": [
                    {
                        "id": "1440",
                        "name": "St\u00e1dn\u00edk Ji\u0159\u00ed",
                        "club": "\u017dK "
                    },
                    {
                        "id": "863",
                        "name": "Pl\u00e1ni\u010dka Michal",
                        "club": "BKP"
                    }
                ]
            },
            "7": {
                "id": 7,
                "title": "Gregor - Hytka",
                "players": [
                    {
                        "id": "3255",
                        "name": "Gregor \u0160t\u011bp\u00e1n",
                        "club": "BKP"
                    },
                    { "id": "3253", "name": "Hytka Jakub", "club": "BKP" }
                ]
            },
            "8": {
                "id": 8,
                "title": "B\u00f6hmov\u00e1 - Kupkov\u00e1",
                "players": [
                    {
                        "id": "1818",
                        "name": "B\u00f6hmov\u00e1 Ji\u0159ina",
                        "club": "BKP"
                    },
                    { "id": "878", "name": "Kupkov\u00e1 Lucie", "club": "BKP" }
                ]
            },
            "21": {
                "id": 21,
                "title": "Frant\u00edk - Korynta",
                "players": [
                    {
                        "id": "299",
                        "name": "Frant\u00edk Franti\u0161ek",
                        "club": "BKP"
                    },
                    { "id": "307", "name": "Korynta Jan", "club": "BKP" }
                ]
            },
            "22": {
                "id": 22,
                "title": "Grusov\u00e1 - Gebertov\u00e1",
                "players": [
                    { "id": "2852", "name": "Grusov\u00e1 Ivana", "club": "BKP" },
                    { "id": "2795", "name": "Gebertov\u00e1 Alena", "club": "BKP" }
                ]
            },
            "23": {
                "id": 23,
                "title": "Ben\u00e1k - Kafka",
                "players": [
                    { "id": "1087", "name": "Ben\u00e1k Jaroslav", "club": "BKP" },
                    { "id": "2581", "name": "Kafka Ond\u0159ej", "club": "BKP" }
                ]
            },
            "24": {
                "id": 24,
                "title": "Havl\u00ednov\u00e1 - Urban",
                "players": [
                    {
                        "id": "2921",
                        "name": "Havl\u00ednov\u00e1 Jitka",
                        "club": "BKP"
                    },
                    { "id": "277", "name": "Urban Jan", "club": "\u017dK " }
                ]
            },
            "25": {
                "id": 25,
                "title": "Havelkov\u00e1 - Kot\u011b\u0161ovcov\u00e1",
                "players": [
                    { "id": "3261", "name": "Havelkov\u00e1 Eva", "club": "BKP" },
                    {
                        "id": "1990",
                        "name": "Kot\u011b\u0161ovcov\u00e1 Jitka",
                        "club": "BKP"
                    }
                ]
            },
            "26": {
                "id": 26,
                "title": "V\u00e1clav\u00edk - Baumann",
                "players": [
                    {
                        "id": "2864",
                        "name": "V\u00e1clav\u00edk Franti\u0161ek",
                        "club": "BKP"
                    },
                    { "id": "2582", "name": "Baumann Martin", "club": "BKP" }
                ]
            },
            "27": {
                "id": 27,
                "title": "Han\u00e1k - Huben\u00fd",
                "players": [
                    { "id": "3531", "name": "Han\u00e1k Jan", "club": "HAV" },
                    { "id": "3601", "name": "Huben\u00fd Jakub", "club": "BKP" }
                ]
            },
            "28": {
                "id": 28,
                "title": "Sad\u00edlkov\u00e1 - Tomanov\u00e1",
                "players": [
                    {
                        "id": "3033",
                        "name": "Sad\u00edlkov\u00e1 Hana",
                        "club": "BKP"
                    },
                    { "id": "2794", "name": "Tomanov\u00e1 Zuzana", "club": "BKP" }
                ]
            }
        },
		"td": {
			"name": "Zdeněk Frabša",
		},
		"rounds": {
            
        }
	}
