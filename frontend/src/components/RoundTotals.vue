<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import RoundGroupTotals from './RoundGroupTotals.vue';

const props = defineProps({
  round: {
    type: Number,
    required: true,
  },
  tournament: {
    type: Object as () => Tournament,
    required: true,
  },
})
</script>

<template>
  <div class=" gap-large flex flex-column justify-center">

    <div v-for="group, i in props.tournament.groups" class="flex flex-column">
      <h3>Skupina {{ group.name }}</h3>

      <RoundGroupTotals  :key="i" :round="props.round" :tournament="props.tournament"
                      :groupIndex="i" />
    </div>

   
  </div>
</template>

