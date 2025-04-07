<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { computed, reactive } from 'vue';
import TournamentGroupTotals from './TournamentGroupTotals.vue';
import { TableRoundResult } from '@/model/MatchResult';
import { Group } from '@/model/modelTypes';

const props = defineProps({
  tournament: {
    type: Object as () => Tournament,
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


const group = reactive(props.tournament.groups[props.groupIndex] as Group);
const results = computed(() => props.tournament.getRoundResults(props.round)?.filter(r => group.players.includes(r.ns) || group.players.includes(r.ew)));

const wasPlayed = computed(() => props.tournament.wasRoundPlayed(props.round));
console.log(results.value);
</script>

<template>


    <div class="flex flex-column justify-center gap-small">
      <template v-if="results.length && wasPlayed">
    <TournamentGroupTotals class="totals" :tournament="tournament" :groupIndex="groupIndex" :untilRound="round" :showMatches="false">
    </TournamentGroupTotals>

    <table  class="table table-totals">
      <tr>
        <th>Stůl</th>
        <th>NS</th>
        <th>EW</th>
        <th colspan="2">IMP</th>
        <th colspan="2">VP</th>
      </tr>
      <tr v-for="result in results" :key="result.ns">
        <td>{{ result.table }}</td>
        <td class="col-name" v-for="key in ['ns', 'ew']" :key="key">
          <router-link 
                       :to="{ name: 'pair-results', params: { pair: result[key as keyof TableRoundResult] } }">
            {{ tournament.getPair(result[key as 'ns' | 'ew'])?.title }}
          </router-link>
        </td>
        <td v-for="key in ['ns', 'ew']" :key="key"> 
          <router-link v-if="result.status === 'played'"
                       :to="{ name: 'round-pair-results', params: { pair: result[key as keyof TableRoundResult], round: props.round } }">
                       {{ result['imp_' + key as keyof TableRoundResult] }}
          </router-link>
          <span v-else title="Dohrávka">
            {{ result['imp_' + key as keyof TableRoundResult] }}
          </span>
         </td>
        <td v-for="key in ['vp_ns', 'vp_ew']" :key="key"> {{ result[key as keyof TableRoundResult] }} </td>
      </tr>
    </table>
      </template>
    <div v-else>
      <p>Výsledky nejsou dostupné.</p>
    </div>
  </div>
    
</template>

<style scoped>


.totals {
  padding-bottom: 20px;
}
</style>
@/model/TableResult