<script setup lang="ts">

import { Tournament } from '@/model/Tournament';

const props = defineProps({
  tournament: {
    type: Object as () => Tournament,
    required: true,
  },
  pair: {
    type: Number,
    required: true,
  }
})


const results = props.tournament.getPairRoundResults(props.pair);
const finalResult = props.tournament.getPairResult(props.pair);

const players = props.tournament.getPair(props.pair)?.players;

</script>

<template>
  <div class="flex flex-column justify-center padding">

    <table class="table">
      <tr>
        <td>VP po posledním kole</td>
        <td class="bold">{{ finalResult.vp.toFixed(2) }}</td>
      </tr>
      <tr>
        <td>Pořadí po posledním kole</td>
        <td class="bold">{{ finalResult.rank }}</td>
      </tr>
    </table>

    <table class="table" v-if="players?.length">
      <tr>
        <th colspan="3">Hráči</th>
      </tr>
      <tr v-for="player in players" :key="player.id">
        <td>{{ player.name }}</td>
        <td><a v-if="player.id" target="_blank" :href="'https://www.matrikacbs.cz/Detail-hrace.aspx?id=' + player.id">{{ player.id }}</a>
        </td>
        <td>{{ player.club }}</td>
      </tr>
    </table>

    <table class="table table-totals" v-if="results.length">
      <tr>
        <th>Kolo</th>
        <th>Soupeři</th>
        <th>IMP</th>
        <th>VP</th>
      </tr>
      <tr v-for="result in results" :key="result.ops">
        <td>
          <router-link :to="{ name: 'round-results', params: { round: result.round } }">
            {{ result.round }}.
          </router-link>
        </td>
        <td class="col-name">
          <router-link :to="{ name: 'pair-results', params: { pair: result.ops } }">
            {{ tournament.getPair(result.ops)?.title }}
          </router-link>
          <span class="small" v-if="result.tableResult.status === 'postponed'"> (dohrávka)</span>
        </td>
        <td>
          <router-link v-if="result.tableResult.status === 'played'"
                       :to="{ name: 'round-pair-results', params: { pair: pair, round: result.round } }">
                       {{ result.imps }} : {{ result.oppResult.imps }}
          </router-link>
          <template v-else-if="result.tableResult.status === 'postponed' ">
            {{ result.imps }} : {{ result.oppResult.imps }}
          </template>
        </td>
        <td>{{ result.vps?.toFixed(2) }}</td>
      </tr>
    </table>
    <div v-else>
      <p>Zatím nejsou dostupné žádné výsledky</p>
    </div>
  </div>
</template>

<style scoped>


.bold {
  font-weight: bold;
}

.small {
  font-size: 0.8em;
}
</style>