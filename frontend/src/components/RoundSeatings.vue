<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { computed } from 'vue';
import { groupRows } from '@/utils/groupRows';
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

const rows = computed(() => groupRows(props.tournament.groups));
</script>

<template>
  <div class="group-rows">
    <div v-for="row, r in rows" :key="r" class="group-row">
      <section v-for="{ group, index } in row" :key="index" class="card table-card">
        <h3>Skupina {{ group.name }}</h3>
        <RoundGroupSeatings :round="props.round" :tournament="props.tournament" :groupIndex="index" />
      </section>
    </div>
  </div>
</template>

