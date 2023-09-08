<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { computed, defineProps, ref } from 'vue';

const props = defineProps({
  tournament: {
    type: Object as () => Tournament | undefined,
    required: true,
  },
  groupIndex: {
    type: Number,
    required: true,
  },
  round: {
    type: Number,
    required: true,
  },
})

const group = ref(props.tournament?.groups[props.groupIndex]);

const seatings = computed(() => {
  if (!props.tournament || !props.tournament.rotations[props.round]) return undefined;
  const a = Object.entries(
    props.tournament.rotations[props.round]
  ).filter(([, { ns, ew }]) => group.value?.players.includes(ns) && group.value?.players.includes(ew))
    .map(([table, { ns, ew }]) => {
      return {
        table: Number.parseInt(table),
        ns: props.tournament!.getPair(ns),
        ew: props.tournament!.getPair(ew),
      }
    })
  console.log(a)
  return a;
})

</script>

<template>
  <div>
    <h3>Skupina {{ group?.name }}</h3>

    <div class="flex flex-column flex-center">

      <table v-if="seatings" class="table table-totals">
        <tr>
          <th>Stůl</th>
          <th>NS</th>
          <th>EW</th>
        </tr>
        <tr v-for="seating in seatings" :key="seating.table">
          <td>{{ seating.table }}</td>
          <td class="col-name" v-for="key in ['ns', 'ew']" :key="key">
            <router-link
                         :to="{ name: 'pair-results', params: { pair: seating[key]?.id ?? 1 } }">
              {{ seating[key]?.title }}
            </router-link>
          </td>
        </tr>
      </table>
      <div v-else>
        <p>Posazení není dostupné.</p>
      </div>
    </div>

  </div>
</template>

<style scoped>
.flex {
  gap: 10px;
}

.totals {
  padding-bottom: 20px;
}
</style>
@/model/TableResult