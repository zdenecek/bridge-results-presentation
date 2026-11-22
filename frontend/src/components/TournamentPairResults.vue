<script setup lang="ts">

import { Tournament, UpcomingMatch } from '@/model/Tournament';
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { formatDate, language, t } from '@/i18n';
import { localName } from '@/i18n/names';
import { buildCalendar, CalendarEvent, googleCalendarUrl, pragueTime } from '@/utils/calendar';
import { downloadTextFile } from '@/utils/download';
import { settings } from '@/settings';
import FollowButton from './partial/FollowButton.vue';

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

const route = useRoute();
const router = useRouter();
const CLUB = "Bridžový klub Praha, Italská 209/17, Praha 2";
const day = (date: Date) => date.toISOString().slice(0, 10);

// Matches still ahead that have a date; a bye is no match.
const calendarMatches = computed(() => {
  const today = new Date().toLocaleDateString('sv-SE');
  return upcoming.filter((m): m is UpcomingMatch & { date: Date } =>
    !!m.date && day(m.date) >= today && !props.tournament.getPair(m.opponent)?.isBye);
});

const slug = String(route.params.tournament);

/** Rounds start at 17:45 and take four hours; a postponed match is a whole day, its time is agreed separately. */
function calendarEvent(match: UpcomingMatch & { date: Date }): CalendarEvent {
  const name = localName(language.value, props.tournament.title, props.tournament.titleEn);
  const url = window.location.origin + router.resolve({ name: 'pair-results', params: { pair: props.pair } }).href;
  const opponent = props.tournament.getPair(match.opponent)?.title ?? '';
  const what = t(match.postponed ? 'calendar.postponed' : 'calendar.round', { n: match.round });
  const event = {
    uid: `${slug}-${props.pair}-${match.round}-${match.table}@vysledky.bkpraha.cz`,
    summary: `${name}, ${what}: ${opponent}`,
    description: `${t('common.table')} ${match.table} ${match.ns ? 'NS' : 'EW'}\n${url}`,
    location: CLUB,
    url,
  };
  return match.postponed
    ? { ...event, day: day(match.date) }
    : { ...event, start: pragueTime(day(match.date), '17:45'), end: pragueTime(day(match.date), '21:45') };
}

const googleLinks = computed(() => new Map<UpcomingMatch, string>(calendarMatches.value.map((m) => [m, googleCalendarUrl(calendarEvent(m))])));

function downloadCalendar() {
  downloadTextFile(`${slug}-${props.pair}.ics`, buildCalendar(calendarMatches.value.map(calendarEvent)), 'text/calendar');
}

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
          <th colspan="4">{{ $t('common.players') }}</th>
        </tr>
        <tr v-for="player in players" :key="player.id">
          <td>{{ player.name }}</td>
          <td><a v-if="player.id" target="_blank" :href="'https://www.matrikacbs.cz/Detail-hrace.aspx?id=' + player.id">{{ player.id }}</a>
          </td>
          <td>{{ player.club }}</td>
          <td class="follow-cell">
            <follow-button v-if="player.id" small :active="settings.followPlayer === player.id"
                           :label="$t('settings.followPlayer')" :active-label="$t('settings.followingPlayer')"
                           @toggle="settings.followPlayer = settings.followPlayer === player.id ? '' : player.id" />
          </td>
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
          <th :colspan="calendarMatches.length ? 5 : 4">{{ $t('tournament.toPlay') }}</th>
        </tr>
        <tr>
          <th>{{ $t('common.roundCol') }}</th>
          <th>{{ $t('tournament.date') }}</th>
          <th>{{ $t('common.table') }}</th>
          <th>{{ $t('common.opponents') }}</th>
          <th v-if="calendarMatches.length"></th>
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
          <td v-if="calendarMatches.length" class="gcal-cell">
            <a v-if="googleLinks.get(match)" :href="googleLinks.get(match)" target="_blank" rel="noopener" class="gcal"
               :title="$t('calendar.google')" :aria-label="$t('calendar.google')">
              <svg viewBox="0 0 20 20" aria-hidden="true">
                <rect x="2.5" y="4" width="15" height="13.5" rx="2" fill="none" stroke="currentColor" stroke-width="1.6" />
                <path d="M2.5 8h15M6.5 2v4M13.5 2v4M10 10.5v5M7.5 13h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
              </svg>
            </a>
          </td>
        </tr>
      </table>
      <div v-if="calendarMatches.length" class="calendar-row">
        <button type="button" class="calendar-button" @click="downloadCalendar">
          <svg viewBox="0 0 20 20" aria-hidden="true">
            <rect x="2.5" y="4" width="15" height="13.5" rx="2" fill="none" stroke="currentColor" stroke-width="1.6" />
            <path d="M2.5 8h15M6.5 2v4M13.5 2v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
          </svg>
          <span>{{ $t('calendar.add') }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>


.bold {
  font-weight: bold;
}

.follow-cell {
  text-align: right;
  width: 1%;
}

.small {
  font-size: 0.8em;
}

.gcal-cell {
  width: 1%;
  text-align: right;
}

.gcal {
  display: inline-flex;
  padding: 3px;
  border-radius: 6px;
  color: var(--felt, #1a881a);
}

.gcal:hover {
  background: #eef5ee;
}

.gcal svg {
  width: 16px;
  height: 16px;
}

.calendar-row {
  display: flex;
  justify-content: center;
  margin-top: 8px;
}

.calendar-button {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 2px 9px 2px 6px;
  border: 1px solid #cfd8cf;
  border-radius: 999px;
  background: white;
  color: var(--felt, #1a881a);
  font: inherit;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
}

.calendar-button:hover {
  border-color: var(--felt, #1a881a);
  background: #eef5ee;
}

.calendar-button svg {
  width: 14px;
  height: 14px;
}
</style>