#!/usr/bin/env python3

import os
import sys
import csv
import json
import datetime


def get_choice(prompt: str, default: bool = None):

    default_hint = "" if default is None else " nic pro y" if default else " nic pro n"
    inp = input(f"{prompt} (y/n{default_hint}): ").lower().strip()

    if inp == "":
        return default

    if inp in ["y", "yes"]:
        return True
    if inp in ["n", "no"]:
        return False

    print("Neplatný vstup, zadejte prosím y nebo n")
    return get_choice(prompt, default)

def get_date(prompt: str, allow_empty: bool = False):

    default_hint = "(povolen prázdný zápis)" if allow_empty else ""
    inp = input(f"{prompt} {default_hint}: ").lower().strip()

    if inp == "" and allow_empty:
        return None

    try:
        return datetime.datetime.strptime(inp, "%d.%m.%Y")
    except:
        print("Neplatný vstup, zadejte prosím datum ve formátu dd.mm.yyyy")
        return get_date(prompt, allow_empty)



def fopen(path: str, rights="r", encoding="utf-8"):

    try:
        file = open(path, rights, encoding=encoding)
    except IOError:
        print(f"Chyba při otevírání souboru {path}.")
        print("Program bude pokračovat, zadejte prosím cestu k souboru znovu: ")
        path = input()
        return fopen(path, rights, encoding)
    else:
        return file


def get_int(prompt: str, default=None):

    while True:
        try:
            inp = input(prompt)
            if inp == "" and default is not None:
                return default
            return int(inp)
        except ValueError:
            print("Neplatný vstup, zadejte prosím celé číslo: ")
            return get_int(prompt)


def parse_rotations(file):
    try:
        res = {}
        for line in map(lambda x: list(map(int, x.split(","))), file.readlines()):
            if len(line) != 4:
                raise RuntimeError("Neplatný formát souboru rotations.csv")

            round, table, ns, ew = line
            round, table, ns, ew = int(round), int(table), int(ns), int(ew)

            if round not in res:
                res[round] = {}

            if table not in res[round]:
                res[round][table] = {}

            res[round][table]["ns"] = ns
            res[round][table]["ew"] = ew

        return res
    except Exception as e:
        print("Chyba při zpracování souboru rotations.csv")
        print(e)
        print("Program bude ukončen")
        exit(1)


def create_result(line, rotation):
    '''
    Opis rozdani Excel

    0   Cislo_rozdania 
    1   Table

    2   Zavazek
    3   Vysledek
    4   HH 

    5   hodnota
    6   T / N (hráno / nehráno) 
    7   Mezera
    8   +/- Imps
    '''
    
    table = line[1]
    if table not in rotation:
        return None
    rotation = rotation[table]

    if (line[6] == "N"):
        return {
            "deal": int(line[0]),
            "ns": rotation["ns"],
            "ew": rotation["ew"],
            "status": "not-played"
        }

    res = int(line[8])

    output = {
        "status": "played",
        "deal":  int(line[0]),
        "ns": rotation["ns"],
        "ew": rotation["ew"],
        "contract": line[2],
        "declarer": line[4],
        "result": line[3],
        "points": int(line[5]),
        "res_ns": res,
        "res_ew": -res,
    }
    
    if line[7] == "*":
        output["rotated"] = True
        
    return output


def create_round_board_data():
    
    # N:JT.A3.KQ43.KQ843 AK42.KJ97.A6.J72 85.862.JT972.T96 Q9763.QT54.85.A5
    
    file = fopen(input("Zadejte cestu k souboru s rozdáními: "), "r", "CP1250")
    
    boards = {}
    
    state = False
    ability_present = False
    
    def save():
        b = {
            "dealer": dealer,
            "vul": vul,
            "deal": deal,
        }
        if ability_present:
            b["ability"] = ability
            b['minimax'] = minimax
            
        boards[board] = b
    
    for line in file:
        if line.startswith("[Board"):
            if state:
                save()
                
            state = True
            board = int(line.split("\"")[1])  
             
        elif line.startswith("[Dealer"):
            dealer = line.split("\"")[1]
        elif line.startswith("[Vulnerable"):
            vul = line.split("\"")[1]    
        elif line.startswith("[Deal"):
            deal = line.split("\"")[1][2:].split(" ")    
        elif line.startswith("[Ability"):
            ability_present = True
            # 'N:43759 E:8A684 S:43749 W:8A684'
            t = line.split("\"")[1]   
            ability = t[2:7] + t[10:15] + t[18:23] + t[26:31] 
            ability = list(map(lambda x: int(x, 16), ability))
        elif line.startswith("[Minimax"):
            minimax = line.split("\"")[1]    
            
    save()
    return boards

def create_round_data(in_excel, rotation, round_n):
    data = []

    for line in in_excel:
        result = create_result(line, rotation)
        if result is None:
            continue
        data.append(result)

    date = get_date("Zadejte datum konání kola", True).strftime("%Y-%m-%d")


    round = {
        'results' : data,
        'date': date,
        'number': round_n,
        'boards': create_round_board_data(),
    }
    

    return round


def create_round(data, path):

    try:
        round_n = int(os.path.basename(path).split("_")[0][-2:])
    except:
        prob = len(data["rounds"])
        round_n = get_int(
            f"Nepodařilo se získat číslo kola, zadejte prosím ručně (prázdné pro {prob}): ", prob)

    with fopen(path) as in_excel:
        r = csv.reader(in_excel)
        data["rounds"][str(round_n)] = create_round_data(
            r, data["rotations"][str(round_n)], round_n)



    return data


def add_cbs_player_data(players, in_hraci_cbs):

    ids = [str(player["id"]) for pair in players for player in pair["players"]]

    found = {}

    for id, firstname, surname, club, club_short, _, gender, number, year, _ in in_hraci_cbs:
        if id in ids:
            found[id] = {
                "name": firstname + " " + surname,
                "club": club_short,
            }

    for pair in players:
        for player in pair["players"]:
            if player["id"] in found.keys():
                player["name"] = found[player["id"]]["name"]
                player["club"] = found[player["id"]]["club"]
            else:
                player["name"] = ""
                player["club"] = ""

    return players


def parse_players(in_csv):
    print("Načítám hráče")

    players = []
    for index, line in enumerate(in_csv):
        players.append({
            "id":  index + 1,
            "title": line[0],
            "players": [{"id": id} for id in line[1:]]
        })

    return players


def create_run():
    out_path = input(
        f"Zadejte cestu k složce, ve které jsou soubory rotations.csv a players.csv (prázdná pro aktuální adresář {os.path.abspath('.')}): ")

    folder = os.path.abspath(out_path)

    with fopen(os.path.join(folder, "rotations.csv")) as in_rotations, \
            fopen(os.path.join(folder, "players.csv")) as in_players, \
            fopen(os.path.join(folder, "Hraci_CBS.csv"), encoding="windows-1250") as in_hraci_csb:

        rotations = parse_rotations(in_rotations)
        pairs = parse_players(csv.reader(in_players))
        pairs = add_cbs_player_data(
            pairs, csv.reader(in_hraci_csb, delimiter=";"))

        print(f"Načteno {len(pairs)} párů (a trojic)")

        return {
            "title":  input('Zadejte název turnaje: '),
            "totalRounds": len(rotations),
            "rotations": rotations,
            "players": pairs,
            "td": input('Zadejte jméno TD: '),
            "rounds": {},
            "postponed": { r:[] for r in range(1, len(rotations) + 1) },
        }


def get_path():
    if len(sys.argv) > 1:
        path = sys.argv[1]
    else:
        path = input(
            "Zadejte název souboru s turnajem (prázdné pro tournament.json): ")
        if path == "":
            path = "tournament.json"

    return path


def main():

    path = get_path() 

    if len(sys.argv) > 2 or not get_choice("Chcete vytvořit nový běh?", False):
        with open(path, "r") as inp:
            standing = json.load(inp)

            if len(sys.argv) > 2:
                inpath = sys.argv[2]
            else:
                inpath = input(
                    "Zadejte cestu k souboru s výsledky skupinovky ve formátu excel (např. soubor Jaro 202306_Opis_Rozdani_Excel_Becko.txt): ")

            output = create_round(standing, inpath)
    else:
        output = create_run()

    with open(path, "w") as f:
        json.dump(output, f, indent=None)
    
    print("Turnaj uložen do souboru " + path)


if __name__ == "__main__":

    try:
        main()
    except KeyboardInterrupt:
        print()
        print("------")
        print("Ukončeno uživatelem")
        print("------")
