export class Rank {
    private static _default = new Rank(1, 1);
    static default(playerCount?: number): Rank {
        if (playerCount) {
            return new Rank( 1, playerCount);
        }
        return this._default;
    }

    constructor(public rank: number, public count: number) {}

    toOrdinal(): number {
        return this.rank;
    }

    toString(): string {
        if (this.count === 1) {
            return this.rank.toString() + ".";
        } else {
            return `${this.rank}. - ${this.rank + this.count - 1}.`;
        }
    }
}
