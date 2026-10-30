<template>
  <div>
    <router-link :to="{ name: 'tournament-results' }" class="back">‹ Celkové výsledky</router-link>
    <h2 class="page-title">{{ pair?.title }}</h2>
    <section class="card table-card pair-card">
      <tournament-pair-results v-if="tournament" :pair="pairNum" :tournament="tournament" />
    </section>
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


