<script setup lang="ts">
import { PairSumResult } from '@/model/MatchResult';
import { Tournament } from '@/model/Tournament';
import { computed, reactive } from 'vue';
import WinnerCupIconPartial from './partial/WinnerCupIconPartial.vue';

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
})


const group = reactive(props.tournament.groups[props.groupIndex]);
const results = computed(() => {
  const r = group.players.map(
    p => props.tournament.getPairResult(p, props.untilRound)
  ).filter(r => r !== undefined) as PairSumResult[];
  r.sort((a, b) => a.rank.toOrdinal() - b.rank.toOrdinal())
  return r;
});

</script>

<template>
  <div class="flex flex-column flex-center">
    <slot name="header">
      <h3>Skupina {{ group.name }}</h3>
    </slot>
    <slot>
      <table class="table table-totals">
        <tr>
          <th>Pořadí</th>
          <th>Pár</th>
          <th>VP</th>
          <th>Průměr</th>
        </tr>
        <tr v-for="result in results" :key="result.pair">
          <td class="col-rank">{{ result.rank }}</td>
          <td class="col-name">
            <winner-cup-icon-partial  class="cup cup-left" :place="result.rank.rank" v-if="showCups && result.rank.rank <= 3"></winner-cup-icon-partial>
            <router-link :to="{ name: 'pair-results', params: { pair: result.pair } }">
              {{ tournament.getPair(result.pair)?.title }}
            </router-link>
            <winner-cup-icon-partial class="cup cup-right" :place="result.rank.rank" v-if="showCups && result.rank.rank <= 3"></winner-cup-icon-partial>

          </td>
          <td> {{ result.vp.toFixed(2) }} </td>
          <td> {{ result.averageAsNumber.toFixed(2) }} </td>
        </tr>
      </table>
    </slot>

  </div>
</template>

<style scoped>

.cup {
  width: .6em;
}

.cup-left {
  margin-right: 0.5em;
}

.cup-right {
  margin-left: 0.5em;
}

</style>
