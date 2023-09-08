<template>
  <div>
    <template v-if="tournament && round">
      <h2>Rozdání z {{ round.number }}. kola</h2>
      <nav>
        <router-link :to="{ name: 'round-results', params: { round: round.number } }">Výsledky kola</router-link>
        <router-link :to="{ name: 'tournament-results' }">Celkové výsledky</router-link>
      </nav>

      <nav class="board-nav">
        <router-link v-for="b in boards" :key="b" :class="{ 'router-link-exact-active': board === b }"
                     :to="{ name: 'round-board-results', params: { round: round.number, board: b } }">{{ b
                     }}</router-link>

        <router-link
                     :to="{ name: 'round-board-results', params: { round: round.number, board: 'all' } }">Zobrazit
          najednou</router-link>
      </nav>
      <nav v-if="board !== 'all'">
        <router-link
                     :to="{ name: 'round-board-results', params: { round: round.number, board: prev } }">Předchozí</router-link>
        <router-link
                     :to="{ name: 'round-board-results', params: { round: round.number, board: next } }">Další</router-link>
      </nav>


      <RoundAllBoardsResults :round="round" :tournament="tournament" v-if="board === 'all'" />
      <RoundBoardResult :round="round" :tournament="tournament" :board="(board as number)" v-else />
    </template>

  </div>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router';

import { Ref, computed, inject } from 'vue';
import { Tournament } from '@/model/Tournament';
import RoundBoardResult from '@/components/RoundBoardResult.vue';
import RoundAllBoardsResults from '@/components/RoundAllBoardsResults.vue';

const route = useRoute();


const tournament = inject('tournament') as Ref<Tournament | undefined>;
const round = computed(() => tournament.value?.rounds[route.params['round'] as string]);

const boards = computed(() => new Set(round.value?.boardResults.map(r => r.deal)));

const board = (() => {
  if (route.params['board'] === 'all') return 'all';
  const board = Number.parseInt(route.params['board'] as string);
  if (!isNaN(board)) return board;
  return 1;
})();

const boardNum = computed(() => board === "all" ? undefined : board);
const boardMax = computed(() => Math.max(...boards.value.keys()));
const boardMin = computed(() => Math.min(...boards.value.values()));

const prev = computed(() => (boardNum.value && boardNum.value > boardMin.value ? boardNum.value - 1 : boardMin.value));
const next = computed(() => (boardNum.value && boardNum.value < boardMax.value ? boardNum.value + 1 : boardMax.value));


</script>

<style scoped>
.board-nav {
  max-width: 660px;
  margin: 0 auto;
}
</style>
