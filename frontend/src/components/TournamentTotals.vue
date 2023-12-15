<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import TournamentGroupTotals from './TournamentGroupTotals.vue';

const props = defineProps({
  tournament: {
    type: Object as () => Tournament,
    required: true,
  },
})
</script>

<template>
  <div class="flex flex-column justify-center" v-for="group, i in props.tournament.groups" :key="i" >
    <h3>Skupina {{ group.name }}</h3>
    <TournamentGroupTotals  :tournament="props.tournament"
                           :groupIndex="i" />
  </div>
</template>

<style scoped>

</style>
