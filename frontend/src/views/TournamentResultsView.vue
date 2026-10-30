<template>
  <div>
    <TournamentTotals v-if="tournament" :tournament="tournament" />
  </div>
</template>

<script setup lang="ts">
import { Tournament } from '@/model/Tournament';
import { Ref, inject } from 'vue';
import TournamentTotals from '@/components/TournamentTotals.vue';

const tournament = inject('tournament') as Ref<Tournament | undefined>;

</script>
