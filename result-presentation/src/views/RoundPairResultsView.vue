<template>
  <div>
    <h2>Výsledky páru {{ pair?.title }} v {{ roundNum }}. kole</h2>

    <nav>
      <router-link :to="{ name: 'round-results', params: { round: roundNum } }">Výsledky kola</router-link>
      <router-link :to="{ name: 'tournament-results' }">Celkové výsledky</router-link>
      <router-link :to="{ name: 'pair-results' }">Celkové výsledky páru</router-link>
      <router-link :to="{ name: 'round-board-results' }">Výsledky rozdání</router-link>
    </nav>

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

const roundNum = Number.parseInt(route.params['round'] as string)
const tournament = inject('tournament') as Ref<Tournament | undefined>;
const round = computed( () =>tournament.value?.rounds[roundNum]);
const pair = computed( () => tournament.value?.getPair(route.params['pair'] as string))


</script>

<style scoped></style>

