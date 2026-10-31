<template>
  <div>
    <template v-if="tournament">
      <nav class="pills" aria-label="Kolo">
        <router-link :to="{ params: { round: rnd } }"
                     :class="{ 'router-link-exact-active': round === rnd }"
                     v-for="rnd in tournament.totalRounds" :key="rnd">{{ $t('common.roundShort', { n: rnd }) }}</router-link>
      </nav>
      <div class="round-head">
        <h2 class="page-title">
          {{ tournament.getRound(round)?.date ? $t('common.roundDate', { n: round, date: formatDate(tournament.getRound(round)?.date) }) : $t('common.round', { n: round }) }}
        </h2>
        <router-link v-show="played" :to="{ name: 'round-board-results', params: { round: round } }">{{ $t('tournament.boards') }}</router-link>
      </div>
      <RoundTotals :round="round" :tournament="tournament" />
    </template>
  </div>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router';
import { formatDate } from '@/i18n';

import RoundTotals from '@/components/RoundTotals.vue';
import { Ref, computed, inject } from 'vue';
import { Tournament } from '@/model/Tournament';

const route = useRoute();




const tournament = inject('tournament') as Ref<Tournament | undefined>;
const standing = computed(() => tournament.value?.standing);

let round = computed(() => {
  let val = Number.parseInt(route.params['round'] as string)
  if (isNaN(val)) val = standing.value ?? 1;
  if (val == 0) return 1;
  return val;
});


const played = computed(() => tournament.value?.wasRoundPlayed(round.value) );

</script>

<style scoped>
.round-head {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 4px 16px;
  margin-bottom: 12px;
}

.round-head .page-title {
  margin: 0;
}
</style>
