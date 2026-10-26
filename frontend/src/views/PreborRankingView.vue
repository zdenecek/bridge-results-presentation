<template>
  <main-layout>
    <template #header>
      {{ ranking?.title ?? 'Přebor' }}
    </template>
    <template #default>
      <p v-if="state === 'loading'" class="note">Načítám pořadí ...</p>
      <p v-else-if="state === 'error'">Pořadí se nepodařilo načíst.</p>
      <div v-else-if="ranking" class="flex flex-column gap-small">
        <h2>{{ ranking.title }} – celkové pořadí</h2>
        <p class="note"><router-link :to="{ name: 'prebor-index' }">Všechny přebory</router-link></p>
        <p v-if="ranking.updated" class="note">
          Po {{ ranking.rounds.length }}. kole hraném {{ formatDate(ranking.updated) }}
        </p>

        <div class="table-scroll">
          <table class="table">
            <thead>
              <tr>
                <th>Pořadí</th>
                <th>Dvojice</th>
                <th>Celkem</th>
                <th>Kol</th>
                <th v-for="n in ranking.totalRounds" :key="n" class="round">
                  <a v-if="roundOf(n)?.url" :href="roundOf(n)!.url!" target="_blank" :title="formatDate(roundOf(n)!.date)">{{ n }}.</a>
                  <span v-else :title="roundOf(n) ? formatDate(roundOf(n)!.date) : undefined">{{ n }}.</span>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="standing in ranking.ranking" :key="standing.name">
                <td>{{ standing.rank }}.</td>
                <td class="col-name">{{ standing.name }}</td>
                <td class="score total">{{ formatScore(standing.total) }}</td>
                <td>{{ standing.played }}</td>
                <td v-for="n in ranking.totalRounds" :key="n" class="score"
                    :class="{ dropped: resultOf(standing, n) && !resultOf(standing, n)!.counted }"
                    :title="titleOf(standing, n)">
                  <template v-if="resultOf(standing, n)">{{ formatScore(resultOf(standing, n)!.score) }}</template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <section class="rules">
          <p>
            Hraje se {{ ranking.totalRounds }} kol, do celkového pořadí se sčítá {{ ranking.best }} nejlepších
            výsledků ({{ ranking.unit }}). Přeškrtnuté výsledky se nezapočítávají.
          </p>
          <p>Po najetí na výsledek se zobrazí, v jaké sestavě dvojice hrála a na kolikátém místě skončila.</p>
        </section>
      </div>
    </template>
  </main-layout>
</template>

<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import PreborApi, { PreborRanking, PreborStanding } from '@/api/PreborApi';
import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const ranking = ref<PreborRanking>();
const state = ref<'loading' | 'loaded' | 'error'>('loading');

watch(() => route.params.slug, (slug) => {
  state.value = 'loading';
  PreborApi.getRanking(String(slug)).then((data) => {
    ranking.value = data;
    state.value = 'loaded';
    document.title = `${data.title} – celkové pořadí`;
  }).catch((e) => {
    console.error(e);
    state.value = 'error';
  });
}, { immediate: true });

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
  return `${formatDate(round.date)}: ${result.rank}. z ${round.pairs}, ${result.lineup}`;
}

function formatDate(date: string): string {
  const [year, month, day] = date.split('-').map(Number);
  return `${day}. ${month}. ${year}`;
}

function formatScore(score: number): string {
  return score.toLocaleString('cs-CZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>

<style scoped>
.note {
  color: #666;
}

.score {
  text-align: right;
  white-space: nowrap;
}

.total {
  font-weight: bold;
}

th.round {
  min-width: 3.5em;
}

@media (min-width: 641px) {
  .col-name {
    min-width: 14em;
  }
}

td.dropped {
  color: #999;
  text-decoration: line-through;
}

.rules {
  max-width: 640px;
  text-align: left;
  color: #444;
}
</style>
