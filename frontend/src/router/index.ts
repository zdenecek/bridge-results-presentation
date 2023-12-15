import {
    createRouter,
    createWebHistory,
    RouteLocationNormalized,
    RouteRecordRaw,
} from "vue-router";
import TournamentView from "../views/TournamentView.vue";

import TournamentResultsView from "../views/TournamentResultsView.vue";
import PairResultsView from "../views/PairResultsView.vue";

import RoundResultsView from "../views/RoundResultsView.vue";
import RoundsResultsView from "../views/RoundsResultsView.vue";
import RoundPairResultsView from "../views/RoundPairResultsView.vue";
import RoundBoardResultsView from "../views/RoundBoardResultsView.vue";
import TournamentCrosstablesView from "../views/TournamentCrosstablesView.vue";

import TournamentsIndexView from "../views/index/TournamentsIndexView.vue";
import AdminView from "../views/admin/AdminView.vue";

import CreateTournamentView from "../views/admin/CreateTournamentView.vue";
import IndexTournamentsView from "../views/admin/IndexTournamentsView.vue";
import EditTournamentView from "../views/admin/EditTournamentView.vue";
import RoundsSeatingsViewVue from "@/views/RoundsSeatingsView.vue";
import FinalResultsViewVue from "@/views/prints/FinalResultsPrint.vue";

const routes: Array<RouteRecordRaw> = [
    {
        path: "/",
        name: "home",
        component: TournamentsIndexView,
    },
    {
        path: "/admin",
        name: "admin",
        component: AdminView,
        children: [
            {
                path: "create",
                name: "admin-tournament-create",
                component: CreateTournamentView,
            },
            {
                path: "",
                name: "admin-tournament-show",
                component: IndexTournamentsView,
            },
            {
                path: "edit/:tournament",
                name: "admin-tournament-edit",
                component: EditTournamentView,
            },
        ]
    },
    {
        path: "/tournament/:tournament",
        name: "tournament",
        component: TournamentView,
        children: [
            {
                path: "",
                name: "tournament-results",
                component: TournamentResultsView,
            },
            {
                path: "seatings/:round?",
                name: "seatings",
                component: RoundsSeatingsViewVue,
            },
            {
                path: "pair/:pair",
                name: "pair-results",
                component: PairResultsView,
            },
            {
                path: "crosstables",
                name: "crosstables",
                component: TournamentCrosstablesView,
            },
            {
                path: "round/:round",
                name: "round-results",
                component: RoundResultsView,
            },
            {
                path: "rounds/:round?",
                name: "rounds-results",
                component: RoundsResultsView,
            },
            {
                path: "round/:round/boards/:board?",
                name: "round-board-results",
                component: RoundBoardResultsView,
            },
            {
                path: "round/:round/pair/:pair",
                name: "round-pair-results",
                component: RoundPairResultsView,
            },
            {
                path: "print-results",
                name: "print-results",
                component: FinalResultsViewVue,
            }
        ],
    },
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL ?? "/"),
    routes,
});

const defaultTitle = "Výsledky turnaje";
router.beforeEach((to, from, next) => {
    const getter = to.meta["title"] as
        | ((route: RouteLocationNormalized) => string)
        | undefined;

    if (getter) document.title = getter(to);
    else document.title = defaultTitle;
    next();
});

export default router;
