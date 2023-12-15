<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import RoundGroupSeatings from './RoundGroupSeatings.vue';

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
  <div class="flex flex-column justify-center">
    <template v-for="group, i in props.tournament.groups" :key="i">

      <h3>Skupina {{ group.name }} </h3>
      <RoundGroupSeatings :round="props.round" :tournament="props.tournament" :groupIndex="i" />
    </template>

  </div>
</template>

