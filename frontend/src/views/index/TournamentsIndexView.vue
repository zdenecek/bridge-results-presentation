<template>
  <main-layout>
    <template #header>
      Výsledky turnajů Bridžového klubu Praha
    </template>
    <template #default>

      <h2>Seznam turnajů</h2>
      <span v-if="!tournaments">Načítám turnaje ...</span>
      <div class="years">
        <section v-for="year in years" :key="year.label" class="year">
          <h3>{{ year.label }}</h3>
          <router-link v-for="tournament in year.tournaments" :key="tournament.slug"
            :to="{ name: 'tournament-results', params: { tournament: tournament.slug } }">
            {{ tournament.name }}
          </router-link>
        </section>
      </div>

    </template>

    <template #footer>

    </template>
  </main-layout>
</template>

<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import TournamentApi from '@/api/TournamentApi';
import { computed, ref } from 'vue';
import TournamentEntry from '@/model/TournamentEntry';

const tournaments = ref(undefined as TournamentEntry[] | undefined);

const SEASONS = ['jaro', 'leto', 'podzim', 'zima'];

function yearOf(tournament: TournamentEntry): number | undefined {
  const matches = tournament.slug.match(/\d{4}/g);
  return matches ? Number.parseInt(matches[matches.length - 1]!) : undefined;
}

function seasonOf(tournament: TournamentEntry): number {
  const index = SEASONS.findIndex(season => tournament.slug.includes(season));
  return index < 0 ? SEASONS.length : index;
}

const years = computed(() => {
  const byYear = new Map<number | undefined, TournamentEntry[]>();
  for (const tournament of tournaments.value ?? []) {
    const year = yearOf(tournament);
    if (!byYear.has(year)) byYear.set(year, []);
    byYear.get(year)!.push(tournament);
  }

  return Array.from(byYear.entries())
    .sort(([a], [b]) => (b ?? 0) - (a ?? 0))
    .map(([year, entries]) => ({
      label: year?.toString() ?? 'Ostatní',
      tournaments: entries.sort((a, b) => seasonOf(a) - seasonOf(b) || a.name.localeCompare(b.name, 'cs')),
    }));
});

TournamentApi.getTournaments().then((data) => {
  tournaments.value = data;
}).catch((e) => {
  console.error(e);
  console.debug('Error loading tournaments');
});

</script>

<style scoped>
.years {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 30px;
  text-align: left;
}

.year {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.year h3 {
  margin-bottom: 4px;
  padding-bottom: 4px;
  border-bottom: 1px solid #ddd;
}
</style>
