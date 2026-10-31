<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { computed } from 'vue';
import { formatDate } from '@/i18n';

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


// A pending postponed match is listed with the matches to be played.
const results = props.tournament.getPairRoundResults(props.pair)
  .filter((r) => !(r.status === "not-played" && props.tournament.getRound(r.round)?.isTablePending(r.tableResult.table)));
const upcoming = props.tournament.getPairUpcomingMatches(props.pair);
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
          <td>{{ $t('tournament.vpAfterLast') }}</td>
          <td class="bold">{{ finalResult.vp.toFixed(2) }}</td>
        </tr>
        <tr>
          <td>{{ $t('tournament.rankAfterLast') }}</td>
          <td class="bold">{{ finalResult.rank }}</td>
        </tr>
      </table>
    </div>

    <div class="table-scroll" v-if="players?.length">
      <table class="table">
        <tr>
          <th colspan="3">{{ $t('common.players') }}</th>
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
          <th colspan="3">{{ $t('tournament.adjustments') }}</th>
        </tr>
        <tr><th>{{ $t('common.roundCol') }}</th><th>+/-</th><th>{{ $t('tournament.reason') }}</th></tr>
        <tr v-for="adjust in adjusts" :key="adjust.round">
          <td>{{ $t('common.roundShort', { n: adjust.round }) }}</td>
          <td>{{ adjust.vpAdjustment.toFixed(2) }}</td>
          <td>{{ adjust.reason }}</td>
        </tr>
        <tr>
          <td colspan="3">{{ $t('common.totalValue', { value: finalResult.vpAdjustment.toFixed(2) }) }}</td>
        </tr>
      </table>
    </div> 

    <div class="table-scroll" v-if="results.length">
      <table class="table table-totals">
        <tr>
          <th>{{ $t('common.roundCol') }}</th>
          <th>{{ $t('common.opponents') }}</th>
          <th>IMP</th>
          <th>VP</th>
        </tr>
        <tr v-for="result in results" :key="result.ops">
          <td>
            <router-link :to="{ name: 'round-results', params: { round: result.round } }">
              {{ $t('common.roundShort', { n: result.round }) }}
            </router-link>
          </td>
          <td class="col-name">
            <router-link :to="{ name: 'pair-results', params: { pair: result.ops } }">
              {{ tournament.getPair(result.ops)?.title }}
            </router-link>
            <span class="small" v-if="result.tableResult.status === 'postponed'">&nbsp;<router-link
                v-if="result.tableResult.session"
                :to="{ name: 'postponed-session', params: { session: result.tableResult.session } }">{{ $t('tournament.postponedLabel') }}</router-link
              ><template v-else>{{ $t('tournament.postponedLabel') }}</template></span>
          </td>
          <td>
            <router-link v-if="result.tableResult.status === 'played'"
                         :to="{ name: 'round-pair-results', params: { pair: pair, round: result.round } }">
                         {{ result.imps }} : {{ result.oppResult.imps }}
            </router-link>
            <router-link v-else-if="result.tableResult.status === 'postponed' && result.tableResult.session"
                         :to="{ name: 'postponed-session-pair', params: { session: result.tableResult.session, pair } }">
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
      <p>{{ $t('tournament.noResults') }}</p>
    </div>

    <div class="table-scroll" v-if="upcoming.length">
      <table class="table table-totals">
        <tr>
          <th colspan="4">{{ $t('tournament.toPlay') }}</th>
        </tr>
        <tr>
          <th>{{ $t('common.roundCol') }}</th>
          <th>{{ $t('tournament.date') }}</th>
          <th>{{ $t('common.table') }}</th>
          <th>{{ $t('common.opponents') }}</th>
        </tr>
        <tr v-for="match in upcoming" :key="`${match.round}-${match.table}`">
          <td>
            <router-link :to="{ name: 'seatings', params: { round: match.round } }">{{ $t('common.roundShort', { n: match.round }) }}</router-link>
          </td>
          <td>{{ formatDate(match.date) }}</td>
          <td>{{ match.table }} {{ match.ns ? 'NS' : 'EW' }}</td>
          <td class="col-name">
            <router-link :to="{ name: 'pair-results', params: { pair: match.opponent } }">
              {{ tournament.getPair(match.opponent)?.title }}
            </router-link>
            <span class="small" v-if="match.postponed">&nbsp;{{ $t('tournament.postponedLower') }}</span>
          </td>
        </tr>
      </table>
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