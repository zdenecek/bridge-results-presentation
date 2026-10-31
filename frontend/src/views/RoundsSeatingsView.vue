<template>
  <div>
    <template v-if="tournament">
      <nav class="pills" aria-label="Kolo">
        <router-link :to="{ params: { round: rnd } }"
          :class="{ 'router-link-exact-active': round === rnd, disabled: false }" v-for="rnd in tournament.totalRounds"
          :key="rnd">
          {{ $t('common.roundShort', { n: rnd }) }}</router-link>
      </nav>
      <h2 class="page-title">{{ date ? $t('tournament.seatingsTitleDate', { n: round, date: formatDate(date) }) : $t('tournament.seatingsTitle', { n: round }) }}</h2>
      <RoundSeatings :round="round" :tournament="tournament" />

    </template>
  </div>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router';
import { formatDate } from '@/i18n';

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
