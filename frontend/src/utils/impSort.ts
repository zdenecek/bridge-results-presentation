import { Ref, computed, ref } from "vue";

export type ImpSort = "board" | "imp-desc" | "imp-asc";

// Remembered per viewer, so the chosen order stays while browsing rounds.
const STORAGE_KEY = "scoreCardSort";

function storedSort(): ImpSort {
    try {
        const value = localStorage.getItem(STORAGE_KEY);
        return value === "imp-desc" || value === "imp-asc" ? value : "board";
    } catch {
        return "board";
    }
}

/** Score card rows sorted by board, or by IMPs with unscored boards last. */
export function useImpSort<T>(
    rows: Ref<T[] | undefined>,
    board: (row: T) => number,
    imps: (row: T) => number | undefined
) {
    const sort = ref<ImpSort>(storedSort());

    const sorted = computed(() => {
        const list = [...(rows.value ?? [])];
        if (sort.value === "board") return list.sort((a, b) => board(a) - board(b));
        const direction = sort.value === "imp-desc" ? -1 : 1;
        return list.sort((a, b) => {
            const x = imps(a);
            const y = imps(b);
            if (x === undefined || y === undefined) return (x === undefined ? 1 : 0) - (y === undefined ? 1 : 0) || board(a) - board(b);
            return (x - y) * direction || board(a) - board(b);
        });
    });

    function sortBy(column: "board" | "imp") {
        sort.value = column === "board" ? "board" : sort.value === "imp-desc" ? "imp-asc" : "imp-desc";
        try {
            localStorage.setItem(STORAGE_KEY, sort.value);
        } catch {
            // Storage disabled: the order just isn't remembered.
        }
    }

    function indicator(column: "board" | "imp"): string {
        if (column === "board") return sort.value === "board" ? "▲" : "↕";
        return sort.value === "imp-desc" ? "▼" : sort.value === "imp-asc" ? "▲" : "↕";
    }

    return { sorted, sortBy, indicator };
}
