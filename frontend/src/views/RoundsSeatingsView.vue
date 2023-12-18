<template>
  <div>
    <h2>Nasazení na {{ round }}. kolo <template v-if="date">- {{ date.toLocaleDateString() }}</template></h2>
    <nav>
      <router-link :to="{ name: 'tournament-results' }">Výsledky</router-link>
    </nav>
    <template v-if="tournament">
      <nav>
        <router-link :to="{ params: { round: rnd } }"
          :class="{ 'router-link-exact-active': round === rnd, disabled: false }" v-for="rnd in tournament.totalRounds"
          :key="rnd">
          {{ rnd }}</router-link>
        <router-link :to="{ params: { round: next } }" v-if="!tournament.isFinished"
          :class="{ 'router-link-exact-active': round === next }">Aktuální</router-link>
      </nav>


      <div class="flex flex-column">
        <RoundSeatings :round="round" :tournament="tournament" />
      </div>

    </template>
  </div>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router';

import { Ref, computed, inject } from 'vue';
import { Tournament } from '@/model/Tournament';
import RoundSeatings from '@/components/RoundSeatings.vue';

const route = useRoute();

const tournament = inject('tournament') as Ref<Tournament | undefined>;
const next = computed(() => Math.min((tournament.value?.standing ?? 0) + 1, tournament.value?.totalRounds ?? 1));


let round = computed(() => {
  const val = Number.parseInt(route.params['round'] as string)
  if (isNaN(val) && next.value) return next.value;
  return val;
});

const date = computed(() => tournament.value?.rounds.get(round.value)?.date);

</script>

<style scoped>
@media print {

  .gap {
    gap: 1em !important;
  }

  h3 {
    margin-top: 0;
    margin-bottom: 0.2em;
  }

  h2 {
    margin-bottom: 1em;
  }
}

@media screen {
  .print-header {
    display: none;
  }
}
</style>
