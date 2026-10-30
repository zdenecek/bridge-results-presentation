<template>
  <main-layout>
    <template #hero>
      <div class="results-hero">
        <router-link :to="{ name: 'home' }" class="crumb">‹ Všechny výsledky</router-link>
        <h1>Bavlnka – žebříček</h1>
        <p v-if="state === 'loading'" class="status">Načítám žebříček…</p>
        <p v-else-if="state === 'error'" class="status">Žebříček se nepodařilo načíst. Zkuste stránku obnovit.</p>
        <p v-else-if="ranking?.updated" class="status">
          Úterní párové turnaje, po turnaji {{ formatDate(ranking.updated) }}
        </p>
      </div>
    </template>

    <template #default>
      <div v-if="ranking" class="results-page ranking">
        <section class="card table-card">
          <h3>Pořadí hráčů</h3>
          <div class="table-scroll">
            <table class="table">
              <thead>
                <tr>
                  <th class="rank">Pořadí</th>
                  <th>Hráč</th>
                  <th class="num">Turnajů</th>
                  <th class="num">Body</th>
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
                          <span class="points">{{ t.points.toLocaleString('cs-CZ', { maximumFractionDigits: 1 }) }}</span>
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
          <h3>Jak se body počítají</h3>
          <p>
            Do žebříčku se počítají výsledky z posledních {{ ranking.rules.window }} turnajů.
            Za každou účast získává hráč {{ ranking.rules.participationPoints }} bodů a za každou
            dvojici, která skončila za ním, dalších {{ ranking.rules.pointsPerPairBeaten }}.
            Při rovnosti pořadí se body rovnoměrně rozdělí mezi všechny stejně umístěné dvojice.
          </p>
          <p>
            Body z posledních {{ ranking.rules.blockSize }} turnajů se započítávají v plné hodnotě.
            Za každých dalších {{ ranking.rules.blockSize }} turnajů se bodový zisk snižuje
            o {{ Math.round(ranking.rules.blockDecay * 100) }} %.
          </p>
          <p>Klepnutím na hráče se zobrazí body z jednotlivých turnajů.</p>
        </section>
      </div>
    </template>
  </main-layout>
</template>

<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import BavlnkaApi, { BavlnkaRanking } from '@/api/BavlnkaApi';
import { reactive, ref } from 'vue';

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

function formatDate(date: string): string {
  const [year, month, day] = date.split('-').map(Number);
  return `${day}. ${month}. ${year}`;
}

function formatPoints(points: number): string {
  return points.toLocaleString('cs-CZ');
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
