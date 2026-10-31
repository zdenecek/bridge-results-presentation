<template>
  <div>
    <router-link :to="{ name: 'round-results', params: { round: roundNum } }" class="back">{{ $t('tournament.backRound', { n: roundNum }) }}</router-link>
    <h2 class="page-title">{{ $t('tournament.pairInRound', { pair: pair?.title ?? '', n: roundNum }) }}</h2>
    <p class="page-lead links">
      <router-link :to="{ name: 'pair-results' }">{{ $t('tournament.pairAllRounds') }}</router-link>
      <router-link v-show="roundHasDeals" :to="{ name: 'round-board-results' }">{{ $t('tournament.roundBoards') }}</router-link>
    </p>

    <template v-if="tournament && round && pair">
      <RoundPairResults :pair="pair?.id" :tournament="tournament" :round="round" />
    </template>
  </div>
</template>

<script setup lang="ts">
import { Tournament } from '@/model/Tournament';
import { Ref, computed, inject } from 'vue';
import { useRoute } from 'vue-router';
import RoundPairResults from '@/components/RoundPairResults.vue';

const route = useRoute();

const roundNum = computed( ( ) => Number.parseInt(route.params['round'] as string))
const tournament = inject('tournament') as Ref<Tournament | undefined>;
const round = computed( () =>tournament.value?.rounds.get(roundNum.value));
const pair = computed( () => tournament.value?.getPair(Number.parseInt(route.params['pair'] as string)))

const roundHasDeals = computed(() => (round.value?.boards?.size ?? 0) > 0);
</script>

<style scoped>
.links {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 18px;
}
</style>

