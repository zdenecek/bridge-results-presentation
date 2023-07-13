<template>
  <div>
    <h2>Výsledky páru {{ pair?.title }}</h2>
    <nav>
      <router-link :to="{ name: 'tournament-results' }">Celkové výsledky</router-link>
      <router-link :to="{ name: 'crosstables' }">Křížové tabulky</router-link>
    </nav>

    <tournament-pair-results v-if="tournament" :pair="pairNum" :tournament="tournament" />
  </div>
</template>

<script setup lang="ts">
import TournamentPairResults from '@/components/TournamentPairResults.vue';
import { Tournament } from '@/model/Tournament';
import { Ref, inject } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const tournament = inject('tournament') as Ref<Tournament | undefined>;

const pair = tournament.value?.getPair(route.params['pair'] as string);
const pairNum = parseInt(route.params['pair'] as string);

</script>


