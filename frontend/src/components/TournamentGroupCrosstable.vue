<script setup lang="ts">

import { PairTableRoundResult } from '@/model/MatchResult';
import { Group, PairNumber } from '@/model/modelTypes';
import { Tournament } from '@/model/Tournament';
import { computed, reactive } from 'vue';

const props = defineProps({
  tournament: {
    type: Object as () => Tournament,
    required: true,
  },
  groupIndex: {
    type: Number,
    required: true,
  },
})

type matrix = (PairTableRoundResult | undefined)[][];

const group = reactive(props.tournament.groups[props.groupIndex] as Group);
const groupSize = group.players.length;

const additionalColumns = computed(() => Math.max(0,props.tournament.totalRounds - group.players.length + 1));
const additionalColumnsTitles = computed(() => Array.from({ length: additionalColumns.value }, (_, i) => i + group.players.length + ". kolo"));

const additionalColumnResults = computed(() => {

  if (additionalColumns.value === 0) return [];

  let res: matrix = Array.from({ length: groupSize }, () => Array(additionalColumns).map(() => undefined));

  for (let i = 0; i < groupSize; i++) {
    for (let j = 0; j < additionalColumns.value; j++) {
      res[i]![j] = props.tournament.getPairRoundResult(group.players[i]!, j + groupSize)?.[0];
    }
  }

  return res;
});

const results = computed(() => {
  let res: matrix = Array.from({ length: groupSize }, () => Array(groupSize).map(() => undefined));

  group.players.forEach((player: PairNumber) => {

    var pairResults = props.tournament.getPairRoundResults(player).filter(result => result.round < groupSize);
    const index = group.players.indexOf(player);
    pairResults.forEach((result: PairTableRoundResult) => {
      res[index]![group.players.indexOf(result.ops)] = result;
    })
  })
  return res;
});

const pairResults = computed(() => props.tournament.getPairResults());


</script>

<template>
 <table class="table table-totals">
        <tr>
          <th colspan="2"></th>
          <th v-for="player in group.players" :key="player" :title="tournament.getPair(player)?.title">{{ player }}</th>
          <th v-for="i in additionalColumnsTitles" :key="i">{{ i }}</th>
          <th>Průměr</th>
          <th>Celkem</th>
          <th>Pořadí</th>
        </tr>
        <tr v-for="player, i in group.players" :key="player">
          <td>{{ player }}</td>
          <td class="col-name"> {{ tournament.getPair(player)?.title }} </td>
          <td v-for="player2, i2 in group.players" :key="player2"
              class="col-vp"
              :class="{ empty: player === player2, missing: (results[i]?.[i2]?.round ?? 100) <= tournament.standing && results[i]?.[i2]?.tableResult.status === 'not-played' }"
              :title="player !== player2 ? ((results[i]?.[i2]?.round.toString() ?? '?') + '. kolo proti ' + tournament.getPair(player2)?.title) : ''">
            <template v-if="player !== player2 && results[i]?.[i2] && results[i]?.[i2]?.tableResult.status !== 'not-played'">
              <router-link v-if="results[i]?.[i2]?.status === 'played'"
                           :to="{ name: 'round-pair-results', params: { round: results[i]?.[i2]?.round, pair: player } }">
                {{ results[i]?.[i2]?.vps }}
              </router-link>
              <span v-else>
                {{ results[i]?.[i2]?.vps }}
              </span>
            </template>
          </td>

          <template v-if="additionalColumns">
            <td
                class="col-vp"
                v-for="result, i2 in additionalColumnResults[i]" :key="i2"
                :title="result ? (result.round + '. kolo proti ' + tournament.getPair(result.ops)?.title) : ''">

              <template v-if="result">

                <router-link v-if="result.status === 'played'"

                             :to="{ name: 'round-pair-results', params: { round: result.round, pair: player } }">
                  {{ result.vps }}
                </router-link>
                <span v-else>
                  {{ result.vps }}
                </span>
            </template>
            </td>
          </template>


          <td>{{ pairResults.get(player)?.average?.toFixed(2) ?? 'N/A' }}</td>
          <td>{{ pairResults.get(player)?.vp.toFixed(2) }}</td>
          <td>{{ pairResults.get(player)?.rank }}</td>
        </tr>

      </table>
</template>

<style scoped lang="scss">

.totals {
  padding-bottom: 20px;
}

.empty {
  background-color: lighten(black, 90%);
}

.missing {
  background-color: lightyellow;
}

.col-vp {
  min-width: 55px;
}
</style>