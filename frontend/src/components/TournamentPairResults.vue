<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { computed } from 'vue';

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

const adjusts = computed(() => {
  return finalResult.adjusts;
});
</script>

<template>
  <div class="flex flex-column justify-center padding">

    <div class="table-scroll">
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
    </div>

    <div class="table-scroll" v-if="players?.length">
      <table class="table">
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
    </div>

    <div class="table-scroll" v-if="adjusts.length">
      <table class="table">
        <tr>
          <th colspan="3">Úpravy a penalizace</th>
        </tr>
        <tr><th>Kolo</th><th>+/-</th><th>Důvod</th></tr>
        <tr v-for="adjust in adjusts" :key="adjust.round">
          <td>{{ adjust.round }}.</td>
          <td>{{ adjust.vpAdjustment.toFixed(2) }}</td>
          <td>{{ adjust.reason }}</td>
        </tr>
        <tr>
          <td colspan="3">Celkem: {{ finalResult.vpAdjustment.toFixed(2) }}</td>
        </tr>
      </table>
    </div> 

    <div class="table-scroll" v-if="results.length">
      <table class="table table-totals">
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
    </div>
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