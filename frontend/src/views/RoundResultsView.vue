<template>
  <div>
    <h2>{{ $t('tournament.roundResults', { n: round }) }}</h2>
    <nav>
      <router-link :to="{ name: 'seatings' }" v-if="!tournament?.isFinished">{{ $t('tournament.tabSeatings') }}</router-link>
      <router-link :to="{ name: 'tournament-results' }">{{ $t('tournament.tabResults') }}</router-link>
      <router-link :to="{ name: 'rounds-results' }">{{ $t('tournament.allRoundsResults') }}</router-link>
      <router-link :to="{ name: 'round-board-results' }">{{ $t('tournament.boards') }}</router-link>
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
