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
import { Ref, computed, inject } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const tournament = inject('tournament') as Ref<Tournament | undefined>;

  const pairNum = computed( ( ) => parseInt(route.params['pair'] as string));
const pair = computed( () =>  tournament.value?.getPair(pairNum.value));

</script>


