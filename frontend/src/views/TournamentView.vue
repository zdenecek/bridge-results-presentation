<template>
  <main-layout>
    <template v-if="!isPrint" #hero>
      <div class="results-hero">
        <router-link :to="{ name: 'home' }" class="crumb">{{ $t('common.allResults') }}</router-link>
        <h1>{{ shownTitle }}</h1>
        <p v-if="status" class="status">{{ status }}</p>
        <nav class="tabs" :aria-label="$t('tournament.sections')">
          <router-link v-for="tab in tabs" :key="tab.name" :to="{ name: tab.name }"
                       :class="{ active: tab.section === section }" :aria-current="tab.section === section ? 'page' : undefined">
            {{ $t(tab.label) }}
          </router-link>
        </nav>
      </div>
    </template>
    <template v-else #header>
      {{ shownTitle }}
    </template>

    <template #default>
      <div :class="{ 'results-page': !isPrint, presentation: !isPrint }">
        <router-view :key="$route.path" v-show="state === 'loaded'"></router-view>
        <p v-show="state === 'loading'" class="page-lead">{{ $t('tournament.loading') }}</p>
        <p v-show="state === 'error'" class="page-lead">{{ error }}</p>
      </div>
    </template>

    <template #footer>
      <div v-if="isPrint && tournament?.td" class="flex flex-column legible">
        <div>
          Vedoucí turnaje: {{ tournament.td.name }}
        </div>
        <div v-if="tournament.td.email">
          Email: <a :href="'mailto:' + tournament.td.email">{{ tournament.td.email }}</a>
        </div>
      </div>
      <div v-else-if="!isPrint" class="page-foot">
        <p v-if="tournament?.td" class="td">
          <span>{{ $t('footer.td') }} <strong>{{ tournament.td.name }}</strong></span>
          <a v-if="tournament.td.email" :href="'mailto:' + tournament.td.email">{{ tournament.td.email }}</a>
          <a v-if="tournament.td.phone" :href="'tel:' + tournament.td.phone">{{ tournament.td.phone }}</a>
          <a v-if="tournament.td.website" :href="tournament.td.website" target="_blank">{{ $t('footer.web') }}</a>
        </p>
        <p class="links">
          <router-link :to="{ name: 'home' }">{{ $t('footer.allResults') }}</router-link>
          <a href="https://www.czechbridge.cz/" target="_blank">{{ $t('footer.federation') }}</a>
        </p>
      </div>
    </template>
  </main-layout>
</template>

<script setup lang="ts">
import { computed, provide, ref, watch } from 'vue';
import TournamentApi from '@/api/TournamentApi';
import { Tournament } from '@/model/Tournament';
import { useRoute } from 'vue-router';
import MainLayout from '@/layouts/MainLayout.vue';
import { formatDate, language, t as tr } from '@/i18n';
import { localName } from '@/i18n/names';

const route = useRoute();
let title = ref('');

let state = ref('loading');
let error = ref('');

let tournament = ref(undefined as Tournament | undefined)
provide('tournament', tournament);

const isPrint = computed(() => route.name === 'print-results');

const shownTitle = computed(() => title.value
  ? localName(language.value, title.value, tournament.value?.titleEn)
  : tr('tournament.defaultTitle'));
watch(shownTitle, (value) => document.title = value);

/** Which tab a page belongs to: a pair's results sit under the totals, boards and postponed matches under the rounds. */
const SECTIONS: Record<string, string> = {
  'tournament-results': 'results',
  'pair-results': 'results',
  'rounds-results': 'rounds',
  'round-results': 'rounds',
  'round-board-results': 'rounds',
  'round-pair-results': 'rounds',
  'postponed-session': 'rounds',
  'postponed-session-pair': 'rounds',
  seatings: 'seatings',
  crosstables: 'crosstables',
};
const section = computed(() => SECTIONS[String(route.name)] ?? 'results');

const tabs = computed(() => [
  { name: 'tournament-results', section: 'results', label: 'tournament.tabResults' },
  { name: 'rounds-results', section: 'rounds', label: 'tournament.tabRounds' },
  ...(tournament.value?.isFinished ? [] : [{ name: 'seatings', section: 'seatings', label: 'tournament.tabSeatings' }]),
  { name: 'crosstables', section: 'crosstables', label: 'tournament.tabCrosstables' },
]);

const status = computed(() => {
  const t = tournament.value;
  if (!t) return '';
  if (t.isFinished) return tr('tournament.finished', { n: t.totalRounds });
  const next = t.getRound(t.standing + 1)?.date;
  const when = formatDate(next);
  if (t.standing === 0) return when ? tr('tournament.firstRound', { date: when }) : '';
  return tr('tournament.afterRound', { n: t.standing, total: t.totalRounds }) + (when ? tr('tournament.nextRound', { n: t.standing + 1, date: when }) : '');
});

watch(route, () => {
  loadTournament();
});
loadTournament();

function loadTournament() {
  TournamentApi.getTournament(route.params['tournament'] as string).then((data) => {
    state.value = 'loaded';
    tournament.value = new Tournament(data.data);
    title.value = data.name;
  }).catch((err) => {
    console.debug('Error loading tournament');
    console.error(err);
    state.value = 'error';

    if (err.response?.status === 404)
      error.value = tr('tournament.notFound');
    else
      error.value = tr('tournament.loadError');
  });
}
</script>

<style>
.legible {
  background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7));
  padding: 1em;
  gap: 4px;
}

.legible:hover {
  background-color: black;
}

.page-foot {
  width: 100%;
  max-width: 1140px;
  padding: 10px 4px;
  color: rgba(255, 255, 255, 0.8);
  font-family: 'Lexend', Verdana, sans-serif;
  font-size: 0.9375rem;
  text-align: left;
}

.page-foot p {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 18px;
  margin: 0 0 8px;
}

.page-foot strong {
  color: white;
  font-weight: 600;
}

.page-foot a {
  color: white;
  text-decoration: underline;
  text-decoration-color: rgba(255, 255, 255, 0.45);
  text-underline-offset: 3px;
}

.page-foot a:hover {
  text-decoration-color: white;
}
</style>
