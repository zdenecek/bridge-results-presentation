import { ResultsPage, ResultsSection, ResultsWriteResult } from "@/api/ResultsApi";
import { InjectionKey, Ref } from "vue";

/** What the section and event editors share with the results admin page. */
export interface ResultsAdminContext {
    season: Ref<string>;
    page: Ref<ResultsPage | undefined>;
    busy: Ref<boolean>;
    tournaments: Ref<{ slug: string; name: string }[]>;
    competitions: Ref<{ slug: string; title: string }[]>;
    /** Runs a write, shows its result and replaces the page with what the server now stores. */
    run: (action: () => Promise<ResultsWriteResult>, success?: string | ((result: any) => string)) => Promise<boolean>;
}

export const resultsAdminKey: InjectionKey<ResultsAdminContext> = Symbol("resultsAdmin");

export const KIND_LABELS: Record<ResultsSection["kind"], string> = {
    group: "nadpis se sekcemi",
    events: "turnaje podle odkazu na výsledky",
    tournament: "skupinovka z této aplikace",
    prebor: "přebor",
    bavlnka: "Bavlnky sezóny",
};

export function allSections(page: ResultsPage | undefined): ResultsSection[] {
    return (page?.sections ?? []).flatMap((s) => [s, ...s.children]);
}
