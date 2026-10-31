<template>
  <main-layout>
    <template #hero>
      <div class="results-hero">
        <router-link :to="{ name: 'home' }" class="crumb">{{ $t('common.allResults') }}</router-link>
        <h1>{{ ranking ? localName(language, ranking.title, ranking.title_en) : $t('prebor.default') }}</h1>
        <p v-if="state === 'loading'" class="status">{{ $t('prebor.loading') }}</p>
        <p v-else-if="state === 'error'" class="status">{{ $t('prebor.error') }}</p>
        <p v-else-if="ranking" class="status">{{ status }}</p>
      </div>
    </template>

    <template #default>
      <div v-if="ranking" class="results-page ranking">
        <section class="card table-card">
          <h3>{{ $t('prebor.standings') }}</h3>
          <p v-if="!ranking.ranking.length" class="empty">{{ $t('prebor.noRounds') }}</p>
          <div v-else class="table-scroll">
            <table class="table">
              <thead>
                <tr>
                  <th class="rank">{{ $t('common.rank') }}</th>
                  <th>{{ $t('prebor.pair') }}</th>
                  <th v-for="n in ranking.totalRounds" :key="n" class="num round wide">
                    <a v-if="roundOf(n)?.url" :href="roundOf(n)!.url!" target="_blank" :title="formatDate(roundOf(n)!.date)">{{ $t('common.roundShort', { n }) }}</a>
                    <span v-else :title="roundOf(n) ? formatDate(roundOf(n)!.date) : undefined">{{ $t('common.roundShort', { n }) }}</span>
                  </th>
                  <th class="num">{{ $t('common.rounds') }}</th>
                  <th class="num">{{ $t('common.total') }}</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="standing in ranking.ranking" :key="standing.name">
                  <tr class="expandable" :class="{ open: open.has(standing.name) }" @click="toggle(standing.name)">
                    <td class="rank">{{ standing.rank }}.</td>
                    <td class="col-name">
                      <button type="button" class="row-toggle" :aria-expanded="open.has(standing.name)">
                        {{ standing.name }}
                      </button>
                    </td>
                    <td v-for="n in ranking.totalRounds" :key="n" class="num wide"
                        :class="{ dropped: resultOf(standing, n) && !resultOf(standing, n)!.counted }"
                        :title="titleOf(standing, n)">
                      <template v-if="resultOf(standing, n)">
                        <a v-if="roundOf(n)?.url" :href="roundOf(n)!.url!" target="_blank" @click.stop>{{ formatScore(resultOf(standing, n)!.score) }}</a>
                        <template v-else>{{ formatScore(resultOf(standing, n)!.score) }}</template>
                      </template>
                    </td>
                    <td class="num muted">{{ standing.played }}</td>
                    <td class="num strong">{{ formatScore(standing.total) }}</td>
                  </tr>
                  <tr v-if="open.has(standing.name)" class="detail">
                    <td :colspan="ranking.totalRounds + 4">
                      <ul class="rounds">
                        <li v-for="result in standing.results" :key="result.number" :class="{ dropped: !result.counted }">
                          <span class="round-no">{{ $t('common.round', { n: result.number }) }}</span>
                          <span class="round-date">{{ roundOf(result.number) ? formatShortDate(roundOf(result.number)!.date) : '' }}</span>
                          <span class="round-place">{{ $t('prebor.roundPlace', { rank: result.rank, pairs: roundOf(result.number)?.pairs ?? '' }) }}</span>
                          <a v-if="roundOf(result.number)?.url" class="round-score" :href="roundOf(result.number)!.url!"
                             target="_blank" @click.stop>{{ formatScore(result.score) }}</a>
                          <span v-else class="round-score">{{ formatScore(result.score) }}</span>
                          <span v-if="result.lineup !== standing.name" class="round-lineup">{{ result.lineup }}</span>
                        </li>
                      </ul>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </section>

        <div class="below">
          <section v-if="ranking.rounds.length" class="card">
            <h3>{{ $t('prebor.playedRounds') }}</h3>
            <ul class="played">
              <li v-for="round in ranking.rounds" :key="round.id">
                <span>{{ $t('common.roundDate', { n: round.number, date: formatDate(round.date) }) }}</span>
                <a v-if="round.url" :href="round.url" target="_blank">{{ $t('common.results') }}</a>
              </li>
            </ul>
          </section>

          <section class="card quiet rules">
            <h3>{{ $t('prebor.howTitle') }}</h3>
            <p>{{ $t('prebor.how', { rounds: ranking.totalRounds, best: ranking.best, unit: ranking.unit }) }}</p>
            <p>{{ $t('prebor.howTap') }}</p>
          </section>
        </div>
      </div>
    </template>
  </main-layout>
</template>

<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import PreborApi, { PreborRanking, PreborStanding } from '@/api/PreborApi';
import { computed, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { formatDate, formatNumber, language, t as tr } from '@/i18n';
import { localName } from '@/i18n/names';

const route = useRoute();
const ranking = ref<PreborRanking>();
const state = ref<'loading' | 'loaded' | 'error'>('loading');
const open = reactive(new Set<string>());

watch(() => route.params.slug, (slug) => {
  state.value = 'loading';
  PreborApi.getRanking(String(slug)).then((data) => {
    ranking.value = data;
    state.value = 'loaded';
    document.title = tr('prebor.pageTitle', { title: localName(language.value, data.title, data.title_en) });
  }).catch((e) => {
    console.error(e);
    state.value = 'error';
  });
}, { immediate: true });

const status = computed(() => {
  const r = ranking.value;
  if (!r) return '';
  const played = r.rounds.length;
  const rule = tr('prebor.rule', { best: r.best, rounds: r.totalRounds });
  if (!played) return tr('prebor.statusSeason', { season: r.season, rule });
  if (played >= r.totalRounds) return tr('prebor.statusDone', { rule });
  return tr('prebor.statusAfter', { n: played, total: r.totalRounds, date: formatDate(r.rounds[played - 1]!.date), rule });
});

function toggle(name: string) {
  if (open.has(name)) open.delete(name);
  else open.add(name);
}

function roundOf(n: number) {
  return ranking.value?.rounds.find((r) => r.number === n);
}

function resultOf(standing: PreborStanding, n: number) {
  return standing.results.find((r) => r.number === n);
}

function titleOf(standing: PreborStanding, n: number): string | undefined {
  const result = resultOf(standing, n);
  const round = roundOf(n);
  if (!result || !round) return undefined;
  return `${formatDate(round.date)}: ${tr('prebor.roundPlace', { rank: result.rank, pairs: round.pairs })}, ${result.lineup}`;
}

function formatShortDate(date: string): string {
  return formatDate(date, true);
}

function formatScore(score: number): string {
  return formatNumber(score, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>

<style scoped>
.ranking {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.empty {
  margin: 0 8px 10px;
  color: var(--ink-muted);
}

.table .muted {
  color: var(--ink-muted);
}

.table th.round a {
  color: var(--ink-muted);
}

.table td.dropped,
.table td.dropped a {
  color: #a3aaa4;
  text-decoration: line-through;
}

/* Ten round columns only where they fit; on a phone the rounds open under the dvojice. */
.wide {
  display: none;
}

@media (min-width: 900px) {
  .wide {
    display: table-cell;
  }
}

.rounds {
  list-style: none;
  margin: 0;
  padding: 2px 0 4px 2.5em;
  display: grid;
  gap: 4px;
}

.rounds li {
  display: grid;
  grid-template-columns: 4.5em 3.5em 1fr auto;
  gap: 0 10px;
  align-items: baseline;
  font-size: 0.875rem;
}

.rounds .round-date,
.rounds .round-place {
  color: var(--ink-muted);
}

.rounds .round-score {
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  text-align: right;
}

.rounds a.round-score {
  color: var(--felt);
  text-decoration: underline;
  text-decoration-color: rgba(26, 136, 26, 0.4);
  text-underline-offset: 3px;
}

.rounds li.dropped .round-score {
  color: #a3aaa4;
  font-weight: 500;
  text-decoration: line-through;
}

.rounds .round-lineup {
  grid-column: 1 / -1;
  color: var(--ink-muted);
  font-size: 0.8125rem;
}

.below {
  display: grid;
  gap: 16px;
  grid-template-columns: repeat(auto-fit, minmax(min(360px, 100%), 1fr));
  align-items: start;
}

.played {
  list-style: none;
  margin: 0;
  padding: 0;
}

.played li {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 6px 0;
  border-bottom: 1px solid var(--rule);
}

.played li:last-child {
  border-bottom: none;
}

.rules p {
  margin: 0 0 8px;
  color: var(--ink-muted);
}
</style>
