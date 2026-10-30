<template>
  <div>
    <template v-if="tournament && session">
      <router-link :to="{ name: 'tournament-results' }" class="back">‹ Celkové výsledky</router-link>
      <h2 class="page-title">Dohrávka {{ session.date?.toLocaleDateString('cs-CZ') }}</h2>

      <div class="table-scroll matches">
        <table class="table table-totals">
          <tr>
            <th class="rank">Kolo</th>
            <th>NS</th>
            <th>EW</th>
            <th colspan="2" class="num">IMP</th>
            <th colspan="2" class="num">VP</th>
          </tr>
          <tr v-for="m in matches" :key="m.index">
            <td class="rank">
              <router-link :to="{ name: 'round-results', params: { round: m.round } }">{{ m.round }}.</router-link>
            </td>
            <td class="col-name" v-for="pair in [m.ns, m.ew]" :key="pair">
              <router-link v-if="pair" :to="{ name: 'pair-results', params: { pair } }">
                {{ tournament.getPair(pair)?.title }}
              </router-link>
            </td>
            <td v-for="[pair, imps] in [[m.ns, m.imp_ns], [m.ew, m.imp_ew]]" :key="pair" class="num">
              <router-link v-if="pair" :to="{ name: 'postponed-session-pair', params: { session: session.id, pair } }">
                {{ imps }}
              </router-link>
            </td>
            <td class="num strong">{{ m.vp.ns.toFixed(2) }}</td>
            <td class="num strong">{{ m.vp.ew.toFixed(2) }}</td>
          </tr>
        </table>
      </div>

      <BoardStepper :boards="session.boardNumbers" :current="board"
                    :to="(b) => ({ name: 'postponed-session', params: { session: session!.id, board: b } })" />

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
import BoardStepper from "@/components/BoardStepper.vue";

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
.matches {
  margin: 10px 0 16px;
  padding: 6px 4px;
  border: 1px solid #e1e5e1;
  border-radius: 12px;
  background: white;
}

.gap {
  gap: 2em;
}

.small {
  font-size: 0.8em;
  text-align: center;
}
</style>
