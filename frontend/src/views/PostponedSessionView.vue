<template>
  <div>
    <template v-if="tournament && session">
      <h2>Dohrávka {{ session.date?.toLocaleDateString('cs-CZ') }}</h2>
      <nav>
        <router-link :to="{ name: 'tournament-results' }">Celkové výsledky</router-link>
      </nav>

      <div class="table-scroll">
        <table class="table table-totals">
          <tr>
            <th>Kolo</th>
            <th>NS</th>
            <th>EW</th>
            <th colspan="2">IMP</th>
            <th colspan="2">VP</th>
          </tr>
          <tr v-for="m in matches" :key="m.index">
            <td>
              <router-link :to="{ name: 'round-results', params: { round: m.round } }">{{ m.round }}.</router-link>
            </td>
            <td class="col-name" v-for="pair in [m.ns, m.ew]" :key="pair">
              <router-link v-if="pair" :to="{ name: 'pair-results', params: { pair } }">
                {{ tournament.getPair(pair)?.title }}
              </router-link>
            </td>
            <td v-for="[pair, imps] in [[m.ns, m.imp_ns], [m.ew, m.imp_ew]]" :key="pair">
              <router-link v-if="pair" :to="{ name: 'postponed-session-pair', params: { session: session.id, pair } }">
                {{ imps }}
              </router-link>
            </td>
            <td>{{ m.vp.ns.toFixed(2) }}</td>
            <td>{{ m.vp.ew.toFixed(2) }}</td>
          </tr>
        </table>
      </div>

      <nav class="board-nav">
        <router-link v-for="b in session.boardNumbers" :key="b"
          :class="{ 'router-link-exact-active': board === b }"
          :to="{ name: 'postponed-session', params: { session: session.id, board: b } }">{{ b }}</router-link>
        <router-link :to="{ name: 'postponed-session', params: { session: session.id, board: 'all' } }">
          Zobrazit najednou
        </router-link>
      </nav>

      <div class="flex flex-column justify-center gap">
        <PostponedSessionBoard v-for="b in shownBoards" :key="b" :board="b" :session="session"
          :tournament="tournament" />
      </div>
      <p class="small">Srovnávací pole tvoří {{ session.fieldTables }} stolů odehraných programem Jack.</p>
    </template>
    <p v-else-if="tournament">Dohrávka nenalezena.</p>
  </div>
</template>

<script setup lang="ts">
import { useRoute } from "vue-router";
import { Ref, computed, inject } from "vue";
import { Tournament } from "@/model/Tournament";
import { calculateVP } from "@/model/VP";
import PostponedSessionBoard from "@/components/PostponedSessionBoard.vue";

const route = useRoute();
const tournament = inject("tournament") as Ref<Tournament | undefined>;
const session = computed(() => tournament.value?.postponedSessions.get(route.params["session"] as string));

const board = computed(() => {
  if (route.params["board"] === "all") return "all";
  const b = Number.parseInt(route.params["board"] as string);
  return isNaN(b) ? session.value?.boardNumbers[0] : b;
});

const shownBoards = computed(() => {
  if (board.value === "all") return session.value?.boardNumbers ?? [];
  return board.value === undefined ? [] : [board.value];
});

const matches = computed(() => (session.value?.matchResults ?? []).filter((r) => r.lines.length > 0).map((r) => ({
  index: r.index,
  round: r.match.round,
  ...tournament.value!.getPostponedSeating(r.match),
  imp_ns: r.seated_imp_ns,
  imp_ew: r.seated_imp_ew,
  vp: calculateVP(r.seated_imp_ns - r.seated_imp_ew),
})));
</script>

<style scoped>
.board-nav {
  max-width: 660px;
  margin: 0 auto;
}

.gap {
  gap: 2em;
}

.small {
  font-size: 0.8em;
  text-align: center;
}
</style>
