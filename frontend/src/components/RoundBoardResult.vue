<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { computed, defineProps } from 'vue';
import Contract from './partial/ContractPartial.vue';
import BoardPartial from './partial/BoardPartial.vue';
import { Round } from '@/model/Round';
import { BoardResult, CompleteBoardResult } from '@/model/BoardResult';

const props = defineProps({
  tournament: {
    type: Object as () => Tournament,
    required: true,
  },
  round: {
    type: Object as () => Round,
    required: true,
  },
  board: {
    type: Number,
    required: true,
  },
})


const results = computed(() => {
  const res = props.round.boardResults.filter(r => r.deal === props.board);
  res.sort((a, b) => {
    if (a.status === 'not-played') return 1;
    if (b.status === 'not-played') return -1;
    return (b as CompleteBoardResult).points - (a as CompleteBoardResult).points;
  });
  return res;
});


const average = computed(() => props.round.getBoardAverage(props.board));
const boardData = computed(() => props.round.boards?.[props.board]);
</script>

<template>
  <div class="flex flex-column flex-center">
    <h3>Rozdání číslo {{ props.board }}</h3>

    <div class="flex flex-column flex-center">
      <BoardPartial v-if="boardData" :board="boardData" :number="board" />
      <div v-else>Detaily rozdání nejsou k dispozici.</div>

      <table class="table table-results">
        <tr>
          <th>NS</th>
          <th>EW</th>
          <th>Závazek</th>
          <th colspan="2">Výsledek</th>
          <th>IMP</th>
        </tr>


        <tr v-for="result in results" :key="result.ns">
          <td class="col-name" v-for="line in ['ns', 'ew']" :key="line">
            <router-link
                         :to="{ name: 'round-pair-results', params: { pair: result[line as keyof BoardResult], round: props.round.number } }">
              {{ tournament.getPair(result[line as 'ns' | 'ew'])?.title }}
            </router-link>
          </td>
          <template v-if="result.status === 'played'">
            <td>
              <Contract :contract="(result as CompleteBoardResult).contract"
                        :declarer="(result as CompleteBoardResult).declarer" />
            </td>
            <td>{{ (result as CompleteBoardResult).result }}</td>
            <td>{{ (result as CompleteBoardResult).points }}</td>
            <td>{{ (result as CompleteBoardResult).res_ns }}</td>
          </template>
          <template v-else-if="result.status === 'not-played'">
            <td colspan="5">Nehráno</td>
          </template>
        </tr>
        <tr v-if="average">

          <td colspan="2"></td>
          <td colspan="4">Průměr: {{ average }}</td>
        </tr>

      </table>
    </div>
  </div>
</template>


<style scoped>
.flex {
  gap: 20px;
}
</style>