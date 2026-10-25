<template>
  <main-layout>
    <template #header>
      Bavlnka – žebříček
    </template>
    <template #default>
      <p v-if="state === 'loading'" class="note">Načítám žebříček ...</p>
      <p v-else-if="state === 'error'">Žebříček se nepodařilo načíst.</p>
      <div v-else-if="ranking" class="flex flex-column gap-small">
        <p v-if="ranking.updated" class="note">Po turnaji hraném {{ formatDate(ranking.updated) }}</p>

        <div class="table-scroll">
          <table class="table">
            <thead>
              <tr>
                <th>Pořadí</th>
                <th>Hráč</th>
                <th>Body</th>
                <th>Turnajů</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="player in ranking.ranking" :key="player.name">
                <tr class="player" @click="toggle(player.name)">
                  <td>{{ player.rank }}.</td>
                  <td class="col-name">{{ player.name }}</td>
                  <td class="points">{{ formatPoints(player.points) }}</td>
                  <td>{{ player.tournaments.length }}</td>
                </tr>
                <tr v-if="open.has(player.name)" class="detail">
                  <td colspan="4">
                    <table class="contributions">
                      <tr v-for="t in player.tournaments" :key="t.id">
                        <td><a :href="urlOf(t.id)" target="_blank">{{ formatDate(t.date) }}</a></td>
                        <td>{{ t.rank }}. z {{ t.pairs }}</td>
                        <td>{{ Math.round(t.weight * 100) }} %</td>
                        <td class="points">{{ t.points.toLocaleString('cs-CZ', { maximumFractionDigits: 1 }) }}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <section class="rules">
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
          <p>Kliknutím na hráče se zobrazí body z jednotlivých turnajů.</p>
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
.note {
  color: #666;
}

tr.player {
  cursor: pointer;
}

.points {
  text-align: right;
}

tr.detail > td {
  background-color: #f7faf7;
}

.contributions {
  margin: 0 auto;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.contributions td {
  border: none;
  padding: 2px 10px;
}

.rules {
  max-width: 640px;
  text-align: left;
  color: #444;
}
</style>
