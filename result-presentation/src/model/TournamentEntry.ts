

export default class TournamentEntry {
    name: string;
    slug: string;
    id: number;
    data?: any;

    constructor(obj: any) {
        const { name, slug, data, id } = obj;
        this.name = name;
        this.slug = slug;
        this.id = id;
        this.data = data;
    }
}