export function unique<T>(array: IterableIterator<T>): Set<T> {
    return new Set(array);
}
