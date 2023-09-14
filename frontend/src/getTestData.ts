import { Tournament, TournamentData } from "./model/Tournament";

export function getData(): Tournament {
    // throw new Error("Not implemented");
     return new Tournament(testdata as TournamentData);
}

const testdata =  {
		"title": "Skupinová B Podzim 2023",
		"totalRounds": 9,
		"groups": [
			{
				"name": "B1",
				"players": [
					1,
					2,
					3,
					4,
					5,
					6,
					7,
					8,
					9,
					10
				]
			},
			{
				"name": "B2",
				"players": [
					11,
					12,
					13,
					14,
					15,
					16,
					17,
					18,
					19,
					20
				]
			},
			{
				"name": "B3",
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
		"td": {
			"name": "Zdeněk Tomis",
			"phone": "732 767 888",
			"email": "zdnek.tomis@gmail.com",
			"website": "https://zdenektomis.eu/cs"
		},
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
					"ns": 22,
					"ew": 27
				},
				"13": {
					"ns": 23,
					"ew": 26
				},
				"14": {
					"ns": 24,
					"ew": 25
				}
			},
			"2": {
				"1": {
					"ns": 2,
					"ew": 10
				},
				"2": {
					"ns": 8,
					"ew": 4
				},
				"3": {
					"ns": 6,
					"ew": 3
				},
				"4": {
					"ns": 1,
					"ew": 9
				},
				"5": {
					"ns": 5,
					"ew": 7
				},
				"6": {
					"ns": 12,
					"ew": 20
				},
				"7": {
					"ns": 18,
					"ew": 14
				},
				"8": {
					"ns": 16,
					"ew": 13
				},
				"9": {
					"ns": 11,
					"ew": 19
				},
				"10": {
					"ns": 15,
					"ew": 17
				},
				"11": {
					"ns": 28,
					"ew": 25
				},
				"12": {
					"ns": 26,
					"ew": 24
				},
				"13": {
					"ns": 27,
					"ew": 23
				},
				"14": {
					"ns": 21,
					"ew": 22
				}
			},
			"3": {
				"1": {
					"ns": 10,
					"ew": 3
				},
				"2": {
					"ns": 9,
					"ew": 5
				},
				"3": {
					"ns": 2,
					"ew": 1
				},
				"4": {
					"ns": 7,
					"ew": 4
				},
				"5": {
					"ns": 6,
					"ew": 8
				},
				"6": {
					"ns": 20,
					"ew": 13
				},
				"7": {
					"ns": 19,
					"ew": 15
				},
				"8": {
					"ns": 12,
					"ew": 11
				},
				"9": {
					"ns": 17,
					"ew": 14
				},
				"10": {
					"ns": 16,
					"ew": 18
				},
				"11": {
					"ns": 22,
					"ew": 28
				},
				"12": {
					"ns": 23,
					"ew": 21
				},
				"13": {
					"ns": 24,
					"ew": 27
				},
				"14": {
					"ns": 25,
					"ew": 26
				}
			},
			"4": {
				"1": {
					"ns": 4,
					"ew": 10
				},
				"2": {
					"ns": 8,
					"ew": 5
				},
				"3": {
					"ns": 7,
					"ew": 9
				},
				"4": {
					"ns": 3,
					"ew": 2
				},
				"5": {
					"ns": 1,
					"ew": 6
				},
				"6": {
					"ns": 14,
					"ew": 20
				},
				"7": {
					"ns": 18,
					"ew": 15
				},
				"8": {
					"ns": 17,
					"ew": 19
				},
				"9": {
					"ns": 13,
					"ew": 12
				},
				"10": {
					"ns": 11,
					"ew": 16
				},
				"11": {
					"ns": 28,
					"ew": 26
				},
				"12": {
					"ns": 27,
					"ew": 25
				},
				"13": {
					"ns": 21,
					"ew": 24
				},
				"14": {
					"ns": 22,
					"ew": 23
				}
			},
			"5": {
				"1": {
					"ns": 10,
					"ew": 5
				},
				"2": {
					"ns": 9,
					"ew": 6
				},
				"3": {
					"ns": 8,
					"ew": 1
				},
				"4": {
					"ns": 4,
					"ew": 3
				},
				"5": {
					"ns": 2,
					"ew": 7
				},
				"6": {
					"ns": 20,
					"ew": 15
				},
				"7": {
					"ns": 19,
					"ew": 16
				},
				"8": {
					"ns": 18,
					"ew": 11
				},
				"9": {
					"ns": 14,
					"ew": 13
				},
				"10": {
					"ns": 12,
					"ew": 17
				},
				"11": {
					"ns": 23,
					"ew": 28
				},
				"12": {
					"ns": 24,
					"ew": 22
				},
				"13": {
					"ns": 25,
					"ew": 21
				},
				"14": {
					"ns": 26,
					"ew": 27
				}
			},
			"6": {
				"1": {
					"ns": 6,
					"ew": 10
				},
				"2": {
					"ns": 1,
					"ew": 7
				},
				"3": {
					"ns": 5,
					"ew": 4
				},
				"4": {
					"ns": 3,
					"ew": 8
				},
				"5": {
					"ns": 9,
					"ew": 2
				},
				"6": {
					"ns": 16,
					"ew": 20
				},
				"7": {
					"ns": 11,
					"ew": 17
				},
				"8": {
					"ns": 15,
					"ew": 14
				},
				"9": {
					"ns": 13,
					"ew": 18
				},
				"10": {
					"ns": 19,
					"ew": 12
				},
				"11": {
					"ns": 28,
					"ew": 27
				},
				"12": {
					"ns": 21,
					"ew": 26
				},
				"13": {
					"ns": 22,
					"ew": 25
				},
				"14": {
					"ns": 23,
					"ew": 24
				}
			},
			"7": {
				"1": {
					"ns": 10,
					"ew": 7
				},
				"2": {
					"ns": 6,
					"ew": 5
				},
				"3": {
					"ns": 4,
					"ew": 9
				},
				"4": {
					"ns": 2,
					"ew": 8
				},
				"5": {
					"ns": 1,
					"ew": 3
				},
				"6": {
					"ns": 20,
					"ew": 17
				},
				"7": {
					"ns": 16,
					"ew": 15
				},
				"8": {
					"ns": 14,
					"ew": 19
				},
				"9": {
					"ns": 12,
					"ew": 18
				},
				"10": {
					"ns": 11,
					"ew": 13
				},
				"11": {
					"ns": 24,
					"ew": 28
				},
				"12": {
					"ns": 25,
					"ew": 23
				},
				"13": {
					"ns": 26,
					"ew": 22
				},
				"14": {
					"ns": 27,
					"ew": 21
				}
			},
			"8": {
				"1": {
					"ns": 8,
					"ew": 10
				},
				"2": {
					"ns": 7,
					"ew": 6
				},
				"3": {
					"ns": 5,
					"ew": 1
				},
				"4": {
					"ns": 3,
					"ew": 9
				},
				"5": {
					"ns": 2,
					"ew": 4
				},
				"6": {
					"ns": 18,
					"ew": 20
				},
				"7": {
					"ns": 17,
					"ew": 16
				},
				"8": {
					"ns": 15,
					"ew": 11
				},
				"9": {
					"ns": 13,
					"ew": 19
				},
				"10": {
					"ns": 12,
					"ew": 14
				}
			},
			"9": {
				"1": {
					"ns": 10,
					"ew": 9
				},
				"2": {
					"ns": 8,
					"ew": 7
				},
				"3": {
					"ns": 6,
					"ew": 2
				},
				"4": {
					"ns": 4,
					"ew": 1
				},
				"5": {
					"ns": 3,
					"ew": 5
				},
				"6": {
					"ns": 20,
					"ew": 19
				},
				"7": {
					"ns": 18,
					"ew": 17
				},
				"8": {
					"ns": 16,
					"ew": 12
				},
				"9": {
					"ns": 14,
					"ew": 11
				},
				"10": {
					"ns": 13,
					"ew": 15
				}
			}
		},
		"players": {
			"1": {
				"id": 1,
				"title": "Kupková - Svoboda",
				"players": [
					{
						"id": "878",
						"name": "Kupková Lucie",
						"club": "BKP"
					},
					{
						"id": "341",
						"name": "Svoboda Otakar",
						"club": "BKP"
					}
				]
			},
			"2": {
				"id": 2,
				"title": "Kopecký - Teňak",
				"players": [
					{
						"id": "2292",
						"name": "Kopecký Michal",
						"club": "BKP"
					},
					{
						"id": "3024",
						"name": "Teňak Pavel",
						"club": "BKP"
					}
				]
			},
			"3": {
				"id": 3,
				"title": "Filip - Rumančík",
				"players": [
					{
						"id": "1502",
						"name": "Filip Anatol",
						"club": "BKP"
					},
					{
						"id": "2943",
						"name": "Rumančík Jakub",
						"club": "HAV"
					}
				]
			},
			"4": {
				"id": 4,
				"title": "Vondráčková - Hradil",
				"players": [
					{
						"id": "2122",
						"name": "Vondráčková Blanka",
						"club": "BKP"
					},
					{
						"id": "44",
						"name": "Hradil Miroslav",
						"club": "BKP"
					}
				]
			},
			"5": {
				"id": 5,
				"title": "Nulíček - Šíma",
				"players": [
					{
						"id": "1188",
						"name": "Nulíček Vladimír",
						"club": "BKP"
					},
					{
						"id": "3585",
						"name": "Šíma Lucien",
						"club": "VŠ "
					}
				]
			},
			"6": {
				"id": 6,
				"title": "Dudek - Šimek",
				"players": [
					{
						"id": "291",
						"name": "Dudek Jiří",
						"club": "BKP"
					},
					{
						"id": "176",
						"name": "Šimek Petr",
						"club": "BKP"
					}
				]
			},
			"7": {
				"id": 7,
				"title": "Stádník - Rubáč",
				"players": [
					{
						"id": "1440",
						"name": "Stádník Jiří",
						"club": "ŽK "
					},
					{
						"id": "1389",
						"name": "Rubáč Tomáš",
						"club": "BKP"
					}
				]
			},
			"8": {
				"id": 8,
				"title": "Spálovský - Bubník",
				"players": [
					{
						"id": "1761",
						"name": "Spálovský Luboš",
						"club": "BKP"
					},
					{
						"id": "3025",
						"name": "Bubník Gerhardt",
						"club": "BKP"
					}
				]
			},
			"9": {
				"id": 9,
				"title": "Benák - Scháňka",
				"players": [
					{
						"id": "1087",
						"name": "Benák Jaroslav",
						"club": "BKP"
					},
					{
						"id": "2683",
						"name": "Scháňka Martin",
						"club": "BKP"
					}
				]
			},
			"10": {
				"id": 10,
				"title": "Slaninová - Peterka",
				"players": [
					{
						"id": "2370",
						"name": "Slaninová Jiřina",
						"club": "BKP"
					},
					{
						"id": "145",
						"name": "Peterka Milan",
						"club": "BKP"
					}
				]
			},
			"11": {
				"id": 11,
				"title": "Rokosová - Santusová",
				"players": [
					{
						"id": "2555",
						"name": "Rokosová Eva",
						"club": "BKP"
					},
					{
						"id": "2554",
						"name": "Santusová Isabela",
						"club": "BKP"
					}
				]
			},
			"12": {
				"id": 12,
				"title": "Havlínová - Urban",
				"players": [
					{
						"id": "2921",
						"name": "Havlínová Jitka",
						"club": "BKP"
					},
					{
						"id": "277",
						"name": "Urban Jan",
						"club": "ŽK "
					}
				]
			},
			"13": {
				"id": 13,
				"title": "Hanáci",
				"players": [
					{
						"id": "876",
						"name": "Hanák Vladimír",
						"club": "BKP"
					},
					{
						"id": "2209",
						"name": "Hanák Vladimír",
						"club": "BKP"
					}
				]
			},
			"14": {
				"id": 14,
				"title": "Václavík - Baumann",
				"players": [
					{
						"id": "2864",
						"name": "Václavík František",
						"club": "BKP"
					},
					{
						"id": "2582",
						"name": "Baumann Martin",
						"club": "BKP"
					}
				]
			},
			"15": {
				"id": 15,
				"title": "Gregor - Hytka",
				"players": [
					{
						"id": "3255",
						"name": "Gregor Štěpán",
						"club": "BKP"
					},
					{
						"id": "3253",
						"name": "Hytka Jakub",
						"club": "BKP"
					}
				]
			},
			"16": {
				"id": 16,
				"title": "Kolací - Suchá",
				"players": [
					{
						"id": "3420",
						"name": "Kolací Stanislav",
						"club": "BKP"
					},
					{
						"id": "1347",
						"name": "Suchá Eva",
						"club": "BKP"
					}
				]
			},
			"17": {
				"id": 17,
				"title": "Dykovi",
				"players": [
					{
						"id": "293",
						"name": "Dyk Petr",
						"club": "BKP"
					},
					{
						"id": "295",
						"name": "Dyková Tamara",
						"club": "BKP"
					}
				]
			},
			"18": {
				"id": 18,
				"title": "Grusová - Gebertová",
				"players": [
					{
						"id": "2852",
						"name": "Grusová Ivana",
						"club": "BKP"
					},
					{
						"id": "2795",
						"name": "Gebertová Alena",
						"club": "BKP"
					}
				]
			},
			"19": {
				"id": 19,
				"title": "Kovář - Vajda",
				"players": [
					{
						"id": "1441",
						"name": "Kovář Milan",
						"club": "BKP"
					},
					{
						"id": "2765",
						"name": "Vajda Tomáš",
						"club": "CHB"
					}
				]
			},
			"20": {
				"id": 20,
				"title": "pauza",
				"players": [
					{
						"id": "",
						"name": "",
						"club": ""
					},
					{
						"id": "",
						"name": "",
						"club": ""
					}
				]
			},
			"21": {
				"id": 21,
				"title": "Lančové",
				"players": [
					{
						"id": "1131",
						"name": "Lančová Milena",
						"club": "BKP"
					},
					{
						"id": "3336",
						"name": "Lančová Adéla",
						"club": "BKP"
					}
				]
			},
			"22": {
				"id": 22,
				"title": "Sadílková - Tomanová",
				"players": [
					{
						"id": "3033",
						"name": "Sadílková Hana",
						"club": "BKP"
					},
					{
						"id": "2794",
						"name": "Tomanová Zuzana",
						"club": "BKP"
					}
				]
			},
			"23": {
				"id": 23,
				"title": "Černovská - Havelková",
				"players": [
					{
						"id": "2895",
						"name": "Černovská Lenka",
						"club": "BKP"
					},
					{
						"id": "3261",
						"name": "Havelková Eva",
						"club": "BKP"
					}
				]
			},
			"24": {
				"id": 24,
				"title": "Janoušková - Lurring",
				"players": [
					{
						"id": "2812",
						"name": "Janoušková Věra",
						"club": "BKP"
					},
					{
						"id": "3089",
						"name": "Lurring Eleanor",
						"club": "BKP"
					}
				]
			},
			"25": {
				"id": 25,
				"title": "Frabša - Loukotka",
				"players": [
					{
						"id": "926",
						"name": "Frabša Zdeněk",
						"club": "BKP"
					},
					{
						"id": "3598",
						"name": "Loukotka Marek Kristian",
						"club": "BKP"
					}
				]
			},
			"26": {
				"id": 26,
				"title": "Jasná - Kotěšovcová",
				"players": [
					{
						"id": "2593",
						"name": "Jasná Jiřina",
						"club": "BKP"
					},
					{
						"id": "1990",
						"name": "Kotěšovcová Jitka",
						"club": "BKP"
					}
				]
			},
			"27": {
				"id": 27,
				"title": "Laňková - Fořtová",
				"players": [
					{
						"id": "",
						"name": "",
						"club": ""
					},
					{
						"id": "1985",
						"name": "Fořtová Eva",
						"club": "BKP"
					}
				]
			},
			"28": {
				"id": 28,
				"title": "Trávníčková - Říha",
				"players": [
					{
						"id": "1132",
						"name": "Trávníčková Jana",
						"club": "BKP"
					},
					{
						"id": "1714",
						"name": "Říha Ivan",
						"club": "BKP"
					}
				]
			}
		},
		"rounds": {
            "1": {
                "overwrites": [
                    {
                        "type": "postponed",
                        "table": 5
                    },
                    {
                        "type": "postponed",
                        "table": 12
                    },
                    {
                        "type": "postponed",
                        "table": 13
                    }
                ]
            }
        }
	}
