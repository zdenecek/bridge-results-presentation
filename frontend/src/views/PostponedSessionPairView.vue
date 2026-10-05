<template>
  <div>
    <h2>Výsledky páru {{ pair?.title }} v dohrávce {{ session?.date?.toLocaleDateString('cs-CZ') }}</h2>

    <nav>
      <router-link :to="{ name: 'postponed-session', params: { session: route.params['session'] } }">Dohrávka</router-link>
      <router-link :to="{ name: 'tournament-results' }">Celkové výsledky</router-link>
      <router-link :to="{ name: 'pair-results' }">Celkové výsledky páru</router-link>
    </nav>

    <div class="flex flex-column justify-center padding" v-if="tournament && session && result && seating">
      <div class="table-scroll">
        <table class="table">
          <tr>
            <th colspan="2">Výsledek</th>
            <th>IMP</th>
            <th>VP</th>
          </tr>
          <tr v-for="[seat, imps, vps] in [['ns', result.seated_imp_ns, vp.ns], ['ew', result.seated_imp_ew, vp.ew]] as const" :key="seat">
            <td>{{ seat.toUpperCase() }}</td>
            <td>
              <router-link v-if="seating[seat]"
                :to="{ name: 'postponed-session-pair', params: { session: session.id, pair: seating[seat] } }">
                {{ tournament.getPair(seating[seat]!)?.title }}
              </router-link>
            </td>
            <td>{{ imps }}</td>
            <td>{{ vps.toFixed(2) }}</td>
          </tr>
        </table>
      </div>

      <div class="table-scroll">
        <table class="table table-results">
          <tr>
            <th>Rozdání</th>
            <th>Závazek</th>
            <th colspan="2">Výsledek</th>
            <th>Průměr</th>
            <th>IMPy</th>
          </tr>
          <tr v-for="board in session.boardNumbers" :key="board">
            <td class="col-board-num">
              <router-link :to="{ name: 'postponed-session', params: { session: session.id, board } }">
                {{ board }}
              </router-link>
            </td>
            <template v-if="lines.get(board)">
              <td>
                <Contract :contract="lines.get(board)!.contract" :declarer="lines.get(board)!.declarer" />
              </td>
              <td>{{ lines.get(board)!.result }}</td>
              <td>{{ lines.get(board)!.points }}</td>
              <td>{{ session.averages.get(board) }}</td>
              <td>{{ sitsNs ? lines.get(board)!.imp : -lines.get(board)!.imp }}</td>
            </template>
            <td v-else colspan="5">Nehráno</td>
          </tr>
        </table>
      </div>
    </div>
    <p v-else-if="tournament && session">Pár v této dohrávce nehrál.</p>
  </div>
</template>

<script setup lang="ts">
import { useRoute } from "vue-router";
import { Ref, computed, inject } from "vue";
import { Tournament } from "@/model/Tournament";
import { ScoredLine } from "@/model/PostponedSession";
import { calculateVP } from "@/model/VP";
import Contract from "@/components/partial/ContractPartial.vue";

const route = useRoute();
const tournament = inject("tournament") as Ref<Tournament | undefined>;
const session = computed(() => tournament.value?.postponedSessions.get(route.params["session"] as string));
const pairNumber = computed(() => Number.parseInt(route.params["pair"] as string));
const pair = computed(() => tournament.value?.getPair(pairNumber.value));

const result = computed(() => session.value?.matchResults.find((r) => {
  const seating = tournament.value!.getPostponedSeating(r.match);
  return seating.ns === pairNumber.value || seating.ew === pairNumber.value;
}));
const seating = computed(() => result.value && tournament.value!.getPostponedSeating(result.value.match));
const sitsNs = computed(() => seating.value?.ns === pairNumber.value);
const vp = computed(() => calculateVP((result.value?.seated_imp_ns ?? 0) - (result.value?.seated_imp_ew ?? 0)));
const lines = computed(() => new Map<number, ScoredLine>((result.value?.lines ?? []).map((l) => [l.deal, l])));
</script>

<style scoped>
.padding {
  padding-top: 20px;
}
</style>
