/**
 * Groups of one level side by side, levels one under another: A1 / A21 A22 / A31 A32.
 * Parallel groups differ only in their last digit (A21, A22); a name with a single digit (A1, B2)
 * is a level of its own.
 */
export function groupRows<T extends { name: string }>(groups: T[]): { group: T; index: number }[][] {
    const rows = new Map<string, { group: T; index: number }[]>();
    groups.forEach((group, index) => {
        const digits = group.name.match(/\d+$/)?.[0] ?? "";
        const level = digits.length >= 2 ? group.name.slice(0, -1) : group.name;
        if (!rows.has(level)) rows.set(level, []);
        rows.get(level)!.push({ group, index });
    });
    return [...rows.values()];
}
