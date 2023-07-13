<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { defineProps } from 'vue';
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

const boards = new Set(props.round.boardResults.map(r => r.deal));

</script>

<template>
  <div class="flex flex-column flex-center gap">
    <RoundBoardResult v-for="board in boards" :key="board" :board="board" :round="props.round"
                      :tournament="props.tournament" />
  </div>
</template>

<style scoped>
   .gap {
    gap: 2em;
   }
</style>
