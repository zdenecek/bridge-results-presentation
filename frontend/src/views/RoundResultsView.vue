<template>
  <div>
    <h2>Výsledky {{ round }}. kola</h2>
    <nav>
      <router-link :to="{ name: 'seatings' }" v-if="!tournament?.isFinished">Nasazení</router-link>
      <router-link :to="{ name: 'tournament-results' }">Celkové výsledky</router-link>
      <router-link :to="{ name: 'rounds-results' }">Výsledky všech kol</router-link>
      <router-link :to="{ name: 'round-board-results' }">Rozdání</router-link>
    </nav>
    <template v-if="tournament">
      <RoundTotals :round="round" :tournament="tournament" />
    </template>
  </div>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router';

import RoundTotals from '@/components/RoundTotals.vue';
import { Ref, inject } from 'vue';
import { Tournament } from '@/model/Tournament';

const route = useRoute();

const round = Number.parseInt(route.params['round'] as string);
const tournament = inject('tournament') as Ref<Tournament | undefined>;
</script>

<style scoped></style>
