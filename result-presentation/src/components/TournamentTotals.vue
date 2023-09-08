<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { defineProps } from 'vue';
import TournamentGroupTotals from './TournamentGroupTotals.vue';

const props = defineProps({
  tournament: {
    type: Object as () => Tournament,
    required: true,
  },
})
</script>

<template>
  <div class="flex flex-column flex-center">
    <TournamentGroupTotals v-for="_, i in props.tournament.groups" :key="i" :tournament="props.tournament"
                           :groupIndex="i" />
  </div>
</template>

<style scoped>
.flex {
  gap: 20px;
}

</style>
