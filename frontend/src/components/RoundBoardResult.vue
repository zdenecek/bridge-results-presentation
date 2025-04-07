<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { computed } from 'vue';
import Contract from './partial/ContractPartial.vue';
import BoardPartial from './partial/BoardPartial.vue';
import { Round } from '@/model/Round';
import { AdjustedBoardResult, BoardResult, PlayedBoardResult } from '@/model/BoardResult';

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
  const res = props.round.boardResults?.filter(r => r.deal === props.board);
  res?.sort((a, b) => {
    if (a.status === 'not-played' || a.status === 'adjusted') return 1;
    if (b.status === 'not-played' || b.status === 'adjusted') return -1;
    return (b as PlayedBoardResult).points - (a as PlayedBoardResult).points;
  });
  return res;
});


const average = computed(() => props.round.getBoardAverage(props.board));
const boardData = computed(() => props.round.boards?.get(props.board));

</script>

<template>
  <div class="flex flex-column justify-center">
    <h3>Rozdání číslo {{ props.board }}</h3>

    <div class="flex flex-column justify-center">
      <BoardPartial v-if="boardData" :board="boardData" :number="board" />
      <div v-else>Detaily rozdání nejsou k dispozici.</div>

      <table class="table table-results" v-if="results">
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
              <Contract :contract="(result as PlayedBoardResult).contract"
                :declarer="(result as PlayedBoardResult).declarer" />
            </td>
            <td>{{ (result as PlayedBoardResult).result }}</td>
            <td>{{ (result as PlayedBoardResult).points }}</td>
            <td>{{ (result as PlayedBoardResult).res_ns }}</td>
          </template>
          <template v-else-if="result.status === 'not-played'">
            <td colspan="5">Nehráno</td>
          </template>
          <template v-else-if="result.status === 'adjusted'">
            <td colspan="4" :title="result.text ?? ''">UV</td>
            <td>{{ (result as AdjustedBoardResult).res_ns }}/{{ (result as AdjustedBoardResult).ew }}</td>
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
