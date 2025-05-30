<script setup lang="ts">
import { PairSumResult } from '@/model/MatchResult';
import { Tournament } from '@/model/Tournament';
import { computed, reactive } from 'vue';
import WinnerCupIconPartial from './partial/WinnerCupIconPartial.vue';
import { Group, PairNumber } from '@/model/modelTypes';

const props = defineProps({
  tournament: {
    type: Object as () => Tournament,
    required: true,
  },
  groupIndex: {
    type: Number,
    required: true,
  },
  untilRound: {
    type: Number,
    required: false,
    default: undefined
  },
  showCups: {
    type: Boolean,
    required: false,
    default: false,
  },
  showMatches: {
    type: Boolean,
    required: false,
    default: true,
  }
})


const group = reactive(props.tournament.groups[props.groupIndex] as Group);
const results = computed(() => {
  const r = group.players.map(
    (p: PairNumber) => props.tournament.getPairResult(p, props.untilRound)
  ).filter( (r: PairSumResult | undefined) => r !== undefined) as PairSumResult[];
  r.sort((a, b) => a.rank.toOrdinal() - b.rank.toOrdinal())
  return r;
});

const includeAdjustsColumn = computed(() => results.value.some(r => r.hasAdjusts));

</script>


<template>
  <table class="table table-totals print-no-break">
    <tr>
      <th>Pořadí</th>
      <th>Pár</th>
      <th v-if="includeAdjustsColumn">+/-</th>
      <th>Průměr</th>
      <th>VP</th>
      <th v-if="!tournament.isFinished && showMatches">zápasy</th>
    </tr>
    <tr v-for="result in results" :key="result.pair">
      <td class="col-rank">{{ result.rank }}</td>
      <td class="col-name">
        <div class="flex gap-small justify-center align-center gap-none">

        <winner-cup-icon-partial class="cup cup-left" :place="result.rank.rank"
          v-if="showCups && result.rank.rank <= 3"></winner-cup-icon-partial>
        <router-link :to="{ name: 'pair-results', params: { pair: result.pair } }">
          {{ tournament.getPair(result.pair)?.title }}
        </router-link>
        <winner-cup-icon-partial class="cup cup-right" :place="result.rank.rank"
          v-if="showCups && result.rank.rank <= 3"></winner-cup-icon-partial>
      </div>

      </td>
      <td v-if="includeAdjustsColumn" :title="result.adjustmentExplanation"> {{ result.hasAdjusts ? result.vpAdjustment.toFixed(2) : "" }} </td>
      <td> {{ result.averageAsNumber.toFixed(2) }} </td>
      <td> {{ result.vp.toFixed(2) }} </td>
      <td v-if="!tournament.isFinished && showMatches"> {{ result.matchCount }} </td>
    </tr>
  </table>
</template>

<style scoped>

.center {
  margin: auto;
}
.cup-left {
  margin-right: 0.5em;
}

.cup-right {
  margin-left: 0.5em;
}



</style>
