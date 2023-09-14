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
  <div class=" gap flex flex-column flex-center">

    <RoundGroupSeatings v-for="_, i in props.tournament.groups" :key="i" :round="props.round" :tournament="props.tournament"
                      :groupIndex="i" />
  </div>
</template>

<style scoped>
.gap {
  gap: 30px;
}
</style>
