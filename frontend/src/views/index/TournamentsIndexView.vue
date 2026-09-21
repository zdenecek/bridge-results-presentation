<template>
  <main-layout>
    <template #header>
      Výsledky turnajů Bridžového klubu Praha
    </template>
    <template #default>

      <h2>Seznam turnajů</h2>
      <p v-if="!tournaments" class="note">Načítám turnaje ...</p>
      <div class="years">
        <section v-for="year in years" :key="year.label" class="year">
          <h3>{{ year.label }}</h3>
          <router-link v-for="tournament in year.tournaments" :key="tournament.slug"
            :to="{ name: 'tournament-results', params: { tournament: tournament.slug } }">
            {{ tournament.label }}
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

/** The year is already the group heading, so drop it from the name. */
function labelOf(tournament: TournamentEntry, year: number | undefined): string {
  if (!year) return tournament.name;
  return tournament.name.replace(new RegExp(`\\s*${year}\\s*$`), '') || tournament.name;
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
      tournaments: entries
        .sort((a, b) => seasonOf(a) - seasonOf(b) || a.name.localeCompare(b.name, 'cs'))
        .map(entry => ({ slug: entry.slug, label: labelOf(entry, year) })),
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
.note {
  color: #666;
}

.years {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 28px 32px;
  max-width: 900px;
  margin: 0 auto;
  padding: 0 12px 20px;
  text-align: left;
}

.year h3 {
  margin: 0 0 8px;
  padding-bottom: 6px;
  border-bottom: 2px solid var(--primary-color);
  font-size: 1.05rem;
  letter-spacing: 0.05em;
}

.year a {
  display: block;
  padding: 9px 10px;
  border-radius: 6px;
  line-height: 1.25;
}

.year a:hover {
  background-color: #eef5ee;
}

@media (max-width: 640px) {
  .years {
    gap: 24px;
    padding: 0 12px 16px;
  }

  .year a {
    padding: 11px 10px;
    border-bottom: 1px solid #eee;
    border-radius: 0;
  }

  .year a:last-child {
    border-bottom: none;
  }
}
</style>
