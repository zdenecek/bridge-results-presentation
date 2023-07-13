<template>
  <div>
    <h2>Výsledky kol a průběžné celkové výsledky</h2>
    <nav>
      <router-link :to="{ name: 'tournament-results' }">Celkové výsledky</router-link>
      <router-link v-show="played" :to="{ name: 'round-board-results', params: { round: round } }">Rozdání</router-link>
      <router-link :to="{ name: 'crosstables' }">Křížové tabulky</router-link>
    </nav>
    <template v-if="tournament">

      <nav>
        <router-link :to="{ params: { round: rnd } }"
                     :class="{ 'router-link-exact-active': round === rnd, disabled: false }"
                     v-for="rnd in tournament.totalRounds" :key="rnd">
          {{ rnd }}</router-link>
        <router-link :to="{ params: { round: standing } }"
                     :class="{ 'router-link-exact-active': round === standing }">poslední hrané</router-link>
      </nav>
      <RoundTotals :round="round" :tournament="tournament" />
    </template>
  </div>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router';

import RoundTotals from '@/components/RoundTotals.vue';
import { Ref, computed, inject } from 'vue';
import { Tournament } from '@/model/Tournament';

const route = useRoute();




const tournament = inject('tournament') as Ref<Tournament | undefined>;
const standing = computed(() => tournament.value?.standing);

let round = computed(() => {
  const val = Number.parseInt(route.params['round'] as string)
  if (isNaN(val) && standing.value) return standing.value;
  return val;
});



const played = computed(() => tournament.value?.getRoundResults(round.value) !== undefined);
</script>

<style scoped></style>
