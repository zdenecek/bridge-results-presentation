<template>
  <main-layout>
    <template #hero>
      <div class="results-hero">
        <router-link :to="{ name: 'home' }" class="crumb">{{ $t('common.allResults') }}</router-link>
        <h1>{{ $t('bavlnka.title') }}</h1>
        <p v-if="state === 'loading'" class="status">{{ $t('bavlnka.loading') }}</p>
        <p v-else-if="state === 'error'" class="status">{{ $t('bavlnka.error') }}</p>
        <p v-else-if="ranking?.updated" class="status">
          {{ $t('bavlnka.status', { date: formatDate(ranking.updated) }) }}
        </p>
      </div>
    </template>

    <template #default>
      <div v-if="ranking" class="results-page ranking">
        <section class="card table-card">
          <h3>{{ $t('bavlnka.ranking') }}</h3>
          <div class="table-scroll">
            <table class="table">
              <thead>
                <tr>
                  <th class="rank">{{ $t('common.rank') }}</th>
                  <th>{{ $t('common.player') }}</th>
                  <th class="num">{{ $t('bavlnka.tournaments') }}</th>
                  <th class="num">{{ $t('bavlnka.points') }}</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="player in ranking.ranking" :key="player.name">
                  <tr class="expandable" :class="{ open: open.has(player.name) }" @click="toggle(player.name)">
                    <td class="rank">{{ player.rank }}.</td>
                    <td class="col-name">
                      <button type="button" class="row-toggle" :aria-expanded="open.has(player.name)">
                        {{ player.name }}
                      </button>
                    </td>
                    <td class="num muted">{{ player.tournaments.length }}</td>
                    <td class="num strong">{{ formatPoints(player.points) }}</td>
                  </tr>
                  <tr v-if="open.has(player.name)" class="detail">
                    <td colspan="4">
                      <ul class="contributions">
                        <li v-for="t in player.tournaments" :key="t.id">
                          <a :href="urlOf(t.id)" target="_blank" @click.stop>{{ formatDate(t.date) }}</a>
                          <span class="muted">{{ t.rank }}. z {{ t.pairs }}</span>
                          <span class="muted">{{ t.weight < 1 ? Math.round(t.weight * 100) + ' %' : '' }}</span>
                          <span class="points">{{ formatNumber(t.points, { maximumFractionDigits: 1 }) }}</span>
                        </li>
                      </ul>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </section>

        <section class="card quiet rules">
          <h3>{{ $t('bavlnka.howTitle') }}</h3>
          <p>
            {{ $t('bavlnka.how1', { window: ranking.rules.window, participation: ranking.rules.participationPoints, beaten: ranking.rules.pointsPerPairBeaten }) }}
          </p>
          <p>
            {{ $t('bavlnka.how2', { block: ranking.rules.blockSize, decay: Math.round(ranking.rules.blockDecay * 100) }) }}
          </p>
          <p>{{ $t('bavlnka.howTap') }}</p>
        </section>
      </div>
    </template>
  </main-layout>
</template>

<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import BavlnkaApi, { BavlnkaRanking } from '@/api/BavlnkaApi';
import { reactive, ref } from 'vue';
import { formatDate, formatNumber } from '@/i18n';

const ranking = ref<BavlnkaRanking>();
const state = ref<'loading' | 'loaded' | 'error'>('loading');
const open = reactive(new Set<string>());

BavlnkaApi.getRanking().then((data) => {
  ranking.value = data;
  state.value = 'loaded';
}).catch((e) => {
  console.error(e);
  state.value = 'error';
});

function toggle(name: string) {
  if (open.has(name)) open.delete(name);
  else open.add(name);
}

function urlOf(id: number): string | undefined {
  return ranking.value?.tournaments.find((t) => t.id === id)?.url;
}

function formatPoints(points: number): string {
  return formatNumber(points);
}
</script>

<style scoped>
.ranking {
  display: grid;
  gap: 16px;
  align-items: start;
}

@media (min-width: 900px) {
  .ranking {
    grid-template-columns: minmax(0, 3fr) minmax(280px, 2fr);
  }
}

.muted {
  color: var(--ink-muted);
}

.contributions {
  list-style: none;
  margin: 0;
  padding: 2px 0 4px 2.5em;
  display: grid;
  gap: 2px;
}

.contributions li {
  display: grid;
  grid-template-columns: 6.5em 1fr 3em auto;
  gap: 10px;
  font-size: 0.875rem;
}

.contributions .points {
  min-width: 3em;
  text-align: right;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}

.rules p {
  margin: 0 0 8px;
  color: var(--ink-muted);
}
</style>
