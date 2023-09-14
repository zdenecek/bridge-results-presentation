

<template>
  <div>
    <h3>Skupina {{ group?.name }}</h3>

    <div class="flex flex-column flex-center">

      <table v-if="seatings" class="table table-totals">
        <tr>
          <th>Stůl</th>
          <th>NS</th>
          <th>EW</th>
          <th v-if="displayPostponedColumn">Odklady</th>
        </tr>
        <tr v-for="seating in seatings" :key="seating.table">
          <td>{{ seating.table }}</td>
          <td class="col-name" v-for="key in ['ns', 'ew']" :key="key">
            <router-link
                         :to="{ name: 'pair-results', params: { pair: seating[key as 'ns' | 'ew']?.id ?? 1 } }">
              {{ seating[key as 'ns' | 'ew']?.title }}
            </router-link>
          </td>
          <td v-if="displayPostponedColumn">
            <span v-if="seating.postponed">  Odloženo </span>
          </td>
        </tr>
      </table>
      <div v-else>
        <p>Posazení není dostupné.</p>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { computed, ref } from 'vue';

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
  if (!props.tournament || !props.tournament.rotations.get(props.round)) return undefined;
  const allSeatings = Object.entries(props.tournament.rotations.get(props.round)!);
  const groupSeatings = allSeatings.filter(([, {ns, ew}]) => group.value?.players.includes(ns) || group.value?.players.includes(ew))
    .map(([table, { ns, ew }]) => {
      return {
        table: table,
        ns: props.tournament!.getPair(ns),
        ew: props.tournament!.getPair(ew),
        postponed: props.tournament!.rounds.get(props.round)?.isTablePostponed(Number.parseInt(table)),
      }
    })
  return groupSeatings;
})

const displayPostponedColumn = computed(() => {
  return seatings.value?.some(s => s.postponed);
})

</script>

<style scoped>
.flex {
  gap: 10px;
}

.totals {
  padding-bottom: 20px;
}
</style>