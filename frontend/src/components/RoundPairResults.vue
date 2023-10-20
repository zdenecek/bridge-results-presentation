<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { unique } from '@/utils/unique';
import { computed } from 'vue';
import Contract from './partial/ContractPartial.vue';
import { Round } from '@/model/Round';
import { AdjustedBoardResult, BoardResult, CompleteBoardResult, PlayedBoardResult } from '@/model/BoardResult';
import { MatchResults } from '@/model/MatchResult';

const props = defineProps({
  tournament: {
    type: Object as () => Tournament,
    required: true,
  },
  round: {
    type: Object as () => Round,
    required: true,
  },
  pair: {
    type: Number,
    required: true,
  },
})


const results = computed(() => {
  const results = props.round.boardResults?.filter(r => r.ew === props.pair || r.ns === props.pair);
  if (results) results.sort((a, b) => a.deal - b.deal);
  return results;
})

const mergeNames = computed(() => {
  if (!results.value) return false;
  return unique(results.value.map(r => r.ns).values()).size === 1 && unique(results.value.map(r => r.ew).values()).size === 1
}

);
const finalResult = computed(() => props.tournament.getPairRoundResult(props.pair, props.round.number));
const ns = computed(() => finalResult.value?.ns);

const imp_ns = computed(() => MatchResults.getImpsNS(finalResult.value?.tableResult));
const imp_ew = computed(() => MatchResults.getImpsEW(finalResult.value?.tableResult));
const vp_ns = computed(() => MatchResults.getVpsNS(finalResult.value?.tableResult));
const vp_ew = computed(() => MatchResults.getVpsEW(finalResult.value?.tableResult));

const averages = computed(() => props.round.hasAverages);

</script>

<template>
  <div class="flex flex-column flex-center" v-if="results">
    <table class="table" v-if="mergeNames">
      <tr>
        <th colspan="2">Výsledek</th>
        <th>IMP</th>
        <th>VP</th>
      </tr>
      <tr v-for="line in ['ns', 'ew']" :key="line">
        <td>
          {{ line.toUpperCase() }}
        </td>
        <td>

          <router-link
            :to="{ name: 'round-pair-results', params: { pair: results[0]?.[line as keyof BoardResult], round: props.round.number } }">
            {{ tournament.getPair(results[0]?.[line as 'ns' | 'ew'])?.title }}
          </router-link>
        </td>
        <td>
          <span v-if="imp_ns !== undefined">{{ line === 'ns' ? imp_ns : imp_ew }}</span>
        </td>
        <td>
          <span v-if="vp_ns !== undefined">
            {{ line === 'ns' ? vp_ns : vp_ew }}
          </span>
        </td>
      </tr>
    </table>

    <table class="table table-results">
      <tr>
        <th>Rozdání</th>
        <template v-if="!mergeNames">
          <th>NS</th>
          <th>EW</th>
        </template>
        <th>Závazek</th>
        <th colspan="2">Výsledek</th>
        <th v-if="averages">Průměr</th>
        <th>IMPy</th>
      </tr>
      <tr v-for="result in results" :key="result.ns">
        <td class="col-board-num">
          <router-link :to="{ name: 'round-board-results', params: { round: round.number, board: result.deal } }">
            {{ result.deal }}
          </router-link>

        </td>

        <template v-if="!mergeNames">
          <td class="col-name" v-for="line in ['ns', 'ew']" :key="line">
            <router-link
              :to="{ name: 'round-pair-results', params: { pair: result[line as keyof BoardResult], round: props.round.number } }">
              {{ tournament.getPair(result[line as 'ns' | 'ew'])?.title }}
            </router-link>
          </td>
        </template>
        <template v-if="result.status === 'played'">
          <td>
            <Contract :contract="(result as PlayedBoardResult).contract"
              :declarer="(result as PlayedBoardResult).declarer" />
          </td>
          <td>{{ (result as PlayedBoardResult).result }}</td>
          <td>{{ (result as PlayedBoardResult).points }}</td>
          <td v-if="averages">{{ round.getBoardAverage(result.deal) }}</td>
          <td>{{ ns ? (result as PlayedBoardResult).res_ns : (result as PlayedBoardResult).res_ew }}</td>
        </template>
        <template v-else-if="result.status === 'adjusted'">
          <td colspan="4">UV: {{  }}</td>
          <td>{{ ns ? (result as AdjustedBoardResult).res_ns : (result as AdjustedBoardResult).res_ew }}</td>
        </template>
        <template v-else-if="result.status === 'not-played'">
          <td colspan="5">Nehráno</td>
        </template>
      </tr>
    </table>
  </div>
</template>

<style scoped>
.flex {
  padding-top: 20px;
  gap: 20px;
}

.spacer {
  height: 60px;
  border: none;
}

.names-header * {
  padding: 2px 10px;
}
</style>