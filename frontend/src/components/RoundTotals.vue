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
  <div class="grid">

    <section v-for="group, i in props.tournament.groups" :key="i" class="card table-card">
      <h3>Skupina {{ group.name }}</h3>

      <RoundGroupTotals :round="props.round" :tournament="props.tournament"
                      :groupIndex="i" />
    </section>

   
  </div>
</template>

