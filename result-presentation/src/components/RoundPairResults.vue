<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { unique } from '@/utils/unique';
import { computed, defineProps } from 'vue';
import Contract from './partial/ContractPartial.vue';
import { Round } from '@/model/Round';
import { BoardResult, CompleteBoardResult } from '@/model/BoardResult';
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


const results = props.round.boardResults.filter(r => r.ew === props.pair || r.ns === props.pair);
results.sort((a, b) => a.deal - b.deal);

const mergeNames = unique(results.map(r => r.ns).values()).size === 1 && unique(results.map(r => r.ew).values()).size === 1;
const finalResult = props.tournament.getPairRoundResult(props.pair, props.round.number);
const ns = finalResult?.ns;

const imp_ns = MatchResults.getImpsNS(finalResult?.tableResult);
const imp_ew = MatchResults.getImpsEW(finalResult?.tableResult);
const vp_ns = MatchResults.getVpsNS(finalResult?.tableResult);
const vp_ew = MatchResults.getVpsEW(finalResult?.tableResult);

const averages = computed( () => props.round.hasAverages);

</script>

<template>
  <div class="flex flex-column flex-center">
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
            {{ tournament.getPair(results[0]?.[line as keyof BoardResult])?.title }}
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
              {{ tournament.getPair(result[line as keyof BoardResult])?.title }}
            </router-link>
          </td>
        </template>
        <template v-if="result.status === 'played'">
          <td>
            <Contract :contract="(result as CompleteBoardResult).contract" :declarer="(result as CompleteBoardResult).declarer" />
          </td>
          <td>{{ (result as CompleteBoardResult).result }}</td>
          <td>{{ (result as CompleteBoardResult).points }}</td>
          <td v-if="averages">{{ round.getBoardAverage(result.deal) }}</td>
          <td>{{ ns ? (result as CompleteBoardResult).res_ns : (result as CompleteBoardResult).res_ew }}</td>
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