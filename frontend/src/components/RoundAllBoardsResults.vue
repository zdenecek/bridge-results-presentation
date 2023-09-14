<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { computed } from 'vue';
import RoundBoardResult from './RoundBoardResult.vue';
import { Round } from '@/model/Round';


const props = defineProps({
  tournament: {
    type: Object as () => Tournament,
    required: true,
  },
  round: {
    type: Object as () => Round,
    required: true,
  },
})


const boards = computed(() => {
  if(!props.tournament || !props.round) return [];
  const iterator = props.round.boards?.keys();
  if(!iterator) return [];
  return Array.from(iterator);
})


</script>

<template>
  <div class="flex flex-column flex-center gap" v-if="boards">
    <RoundBoardResult  v-for="board in boards" :key="board" :board="board" :round="round"
                      :tournament="tournament" />
  </div>
</template>

<style scoped>
   .gap {
    gap: 2em;
   }
</style>
