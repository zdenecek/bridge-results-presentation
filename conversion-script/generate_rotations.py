
rotationsA = {'even': {6: [(1, 1, 6, 1), (1, 2, 3, 4), (1, 3, 5, 2), (2, 1, 1, 5), (2, 2, 6, 3), (2, 3, 2, 4), (3, 1, 4, 1), (3, 2, 3, 2), (3, 3, 5, 6), (4, 1, 1, 3), (4, 2, 2, 6), (4, 3, 4, 5), (5, 1, 2, 1), (5, 2, 5, 3), (5, 3, 6, 4)], 8: [(1, 1, 8, 1), (1, 2, 2, 7), (1, 3, 6, 3), (1, 4, 4, 5), (2, 1, 1, 7), (2, 2, 8, 2), (2, 3, 3, 5), (2, 4, 6, 4), (3, 1, 5, 1), (3, 2, 2, 6), (3, 3, 7, 3), (3, 4, 4, 8), (4, 1, 1, 4), (4, 2, 3, 2), (4, 3, 5, 8), (4, 4, 7, 6), (5, 1, 3, 1), (5, 2, 2, 4), (5, 3, 7, 5), (5, 4, 6, 8)], 10: [(1, 1, 7, 1), (1, 2, 2, 9), (1, 3, 6, 3), (1, 4, 4, 10), (1, 5, 8, 5), (2, 1, 1, 7), (2, 2, 9, 2), (2, 3, 3, 6), (2, 4, 10, 4), (2, 5, 5, 8), (3, 1, 1, 6), (3, 2, 8, 2), (3, 3, 3, 9), (3, 4, 7, 4), (3, 5, 4, 10), (4, 1, 1, 3), (4, 2, 2, 5), (4, 3, 8, 3), (4, 4, 6, 9), (4, 5, 10, 7), (5, 1, 1, 3), (5, 2, 4, 2), (5, 3, 5, 6), (5, 4, 9, 7), (5, 5, 8, 10)]}, 'odd': {6: [(1, 1, 1, 6), (1, 2, 4, 3), (1, 3, 2, 5), (2, 1, 5, 1), (2, 2, 3, 6), (2, 3, 4, 2), (3, 1, 1, 4), (3, 2, 2, 3), (3, 3, 6, 5), (4, 1, 3, 1), (4, 2, 6, 2), (4, 3, 5, 4), (5, 1, 1, 2), (5, 2, 3, 5), (5, 3, 4, 6)], 8: [(1, 1, 1, 8), (1, 2, 7, 2), (1, 3, 3, 6), (1, 4, 5, 4), (2, 1, 7, 1), (2, 2, 2, 8), (2, 3, 5, 3), (2, 4, 4, 6), (3, 1, 1, 5), (3, 2, 6, 2), (3, 3, 3, 7), (3, 4, 8, 4), (4, 1, 4, 1), (4, 2, 2, 3), (4, 3, 8, 5), (4, 4, 6, 7), (5, 1, 1, 3), (5, 2, 4, 2), (5, 3, 5, 7), (5, 4, 8, 6)], 10: [(1, 1, 1, 10), (1, 2, 6, 2), (1, 3, 3, 7), (1, 4, 8, 4), (1, 5, 5, 9), (2, 1, 10, 1), (2, 2, 2, 6), (2, 3, 7, 3), (2, 4, 4, 8), (2, 5, 9, 5), (3, 1, 7, 1), (3, 2, 2, 9), (3, 3, 6, 3), (3, 4, 4, 10), (3, 5, 8, 5), (4, 1, 1, 6), (4, 2, 8, 2), (4, 3, 3, 9), (4, 4, 7, 4), (4, 5, 5, 10), (5, 1, 6, 1), (5, 2, 2, 8), (5, 3, 9, 3), (5, 4, 4, 7), (5, 5, 10, 5)]}}

b10 = [(1, 1, 10, 1), (1, 2, 9, 8), (1, 3, 7, 3), (1, 4, 5, 2), (1, 5, 4, 6), (2, 1, 2, 10), (2, 2, 8, 4), (2, 3, 6, 3), (2, 4, 1, 9), (2, 5, 5, 7), (3, 1, 10, 3), (3, 2, 9, 5), (3, 3, 2, 1), (3, 4, 7, 4), (3, 5, 6, 8), (4, 1, 4, 10), (4, 2, 8, 5), (4, 3, 7, 9), (4, 4, 3, 2), (4, 5, 1, 6), (5, 1, 10, 5), (5, 2, 9, 6), (5, 3, 8, 1), (5, 4, 4, 3), (5, 5, 2, 7), (6, 1, 6, 10), (6, 2, 1, 7), (6, 3, 5, 4), (6, 4, 3, 8), (6, 5, 9, 2), (7, 1, 10, 7), (7, 2, 6, 5), (7, 3, 4, 9), (7, 4, 2, 8), (7, 5, 1, 3), (8, 1, 8, 10), (8, 2, 7, 6), (8, 3, 5, 1), (8, 4, 3, 9), (8, 5, 2, 4), (9, 1, 10, 9), (9, 2, 8, 7), (9, 3, 6, 2), (9, 4, 4, 1), (9, 5, 3, 5)]
b8 = [(1, 1, 3, 6), (1, 2, 2, 7), (1, 3, 4, 5), (1, 4, 8, 1), (2, 1, 1, 3), (2, 2, 4, 7), (2, 3, 6, 5), (2, 4, 2, 8), (3, 1, 5, 1), (3, 2, 4, 2), (3, 3, 7, 6), (3, 4, 3, 8), (4, 1, 1, 7), (4, 2, 5, 3), (4, 3, 6, 2), (4, 4, 8, 4), (5, 1, 2, 1), (5, 2, 6, 4), (5, 3, 3, 7), (5, 4, 5, 8), (6, 1, 1, 4), (6, 2, 7, 5), (6, 3, 3, 2), (6, 4, 8, 6), (7, 1, 1, 6), (7, 2, 2, 5), (7, 3, 4, 3), (7, 4, 7, 8)]
rotationsB = {
    "even": {
        8: b8,  
        10: b10
    },
    "odd": {
        8: b8,
        10: b10
    }
}


groups = [
    ("A1", 6),
    ("A21", 6),
    ("A22", 6),
    ("A31", 8),
    ("A32", 8),
]

rotations = rotationsA

# groups = [
#     ("B1", 10),
#     ("B2", 10),
#     ("B3", 8),
# ]

# rotations = rotationsB



first_player_num = 1
first_table_num = 1
movements = []

for group, size in groups:

    parity = "odd" if group[-1] == "1" else "even"
    for roundn,tablen, ns, ew in rotations[parity][size]:
        movements.append((roundn,tablen + first_table_num - 1,  ns + first_player_num -1 , ew + first_player_num -1 )) 
    
    first_table_num += size // 2
    first_player_num += size
    

import csv

with open('rotations.csv', 'w') as f:
    writer = csv.writer(f)
    writer.writerows(movements)