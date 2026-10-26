<template>
  <main-layout>
    <template #hero>
      <div class="results-hero">
        <a href="https://www.bkpraha.cz/" class="club">
          <img src="@/assets/logo.png" alt="" class="club-logo">
          <span>Bridžový klub Praha</span>
        </a>
        <h1>Výsledky <span class="season">{{ seasonLabel(season) }}</span></h1>
        <div v-if="page && season !== current" class="seasons" role="navigation" aria-label="Sezóny">
          <router-link v-for="s in page.seasons" :key="s" :to="s === current ? { name: 'home' } : { name: 'results', params: { season: s } }"
                       :class="{ active: s === season }" :aria-current="s === season ? 'page' : undefined">{{ seasonLabel(s) }}</router-link>
        </div>
      </div>
    </template>
    <template #default>
      <div class="results-page">
        <p v-if="state === 'loading'" class="status">Načítám výsledky…</p>
        <p v-else-if="state === 'error'" class="status">Výsledky se nepodařilo načíst. Zkuste stránku obnovit.</p>
        <p v-else-if="page && !page.sections.length" class="status">Pro sezónu {{ seasonLabel(season) }} zatím nejsou žádné výsledky.</p>

        <results-section-block v-for="section in sections" :key="section.id" :section="section" />

        <section v-if="page" class="archive" aria-labelledby="archive-title">
          <h2 id="archive-title">Archiv</h2>
          <div v-if="pastSeasons.length" class="archive-seasons">
            <router-link v-for="s in pastSeasons" :key="s" :to="{ name: 'results', params: { season: s } }">Sezóna {{ seasonLabel(s) }}</router-link>
          </div>
          <p>
            Prezentace všech skupinovek od roku 2023 najdete v <router-link :to="{ name: 'tournaments' }">přehledu skupinovek</router-link>.
          </p>
        </section>
      </div>
    </template>
  </main-layout>
</template>

<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import ResultsApi, { ResultsPage, ResultsSection, currentSeason, seasonLabel } from '@/api/ResultsApi';
import ResultsSectionBlock from '@/components/results/ResultsSectionBlock.vue';
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const current = currentSeason();
const season = computed(() => String(route.params.season || current));
const page = ref<ResultsPage>();
const state = ref<'loading' | 'loaded' | 'error'>('loading');
const pastSeasons = computed(() => (page.value?.seasons ?? []).filter((s) => s !== season.value && s < current));

/** In the season being played the latest tournament comes first; a closed season reads in date order. */
const sections = computed(() => {
  const list = page.value?.sections ?? [];
  if (season.value !== current) return list;
  const newestFirst = (section: ResultsSection): ResultsSection => ({
    ...section,
    events: section.events ? [...section.events].reverse() : section.events,
    children: section.children.map(newestFirst),
  });
  return list.map(newestFirst);
});

watch(season, (s) => {
  state.value = 'loading';
  document.title = `Výsledky ${seasonLabel(s)} – BK Praha`;
  ResultsApi.getPage(s).then((data) => {
    page.value = data;
    state.value = 'loaded';
  }).catch((e) => {
    console.error(e);
    state.value = 'error';
  });
}, { immediate: true });
</script>

<style>
/* Shared by the results components. */
.results-page,
.results-hero {
  --felt: #1a881a;
  --felt-deep: #0e5a1b;
  --suit-black: #000;
  --ink: #222;
  --ink-muted: #5f6661;
  --rule: #e3e6e3;
  --page: #eef1ee;

  font-family: 'Lexend', Verdana, sans-serif;
}

.content:has(> .container > .results-page) {
  background: var(--page, #eef1ee);
}

.content:has(> .container > .results-page) > .container {
  max-width: 1140px;
}

.results-page {
  font-size: 1rem;
  line-height: 1.6;
  color: var(--ink);
  text-align: left;
}

.results-page a {
  color: var(--felt);
  text-decoration: none;
  font-weight: 600;
}

.results-page a:hover {
  color: var(--felt-deep);
  text-decoration: underline;
  text-underline-offset: 3px;
}

.results-page a:focus-visible,
.results-hero a:focus-visible {
  outline: 2px solid currentColor;
  outline-offset: 3px;
  border-radius: 4px;
}

.results-page .card {
  position: relative;
  background: white;
  border-radius: 12px;
  padding: 16px 18px 14px;
  border: 1px solid #e1e5e1;
  overflow: hidden;
}


.results-page .card h3 {
  font-size: 1.0625rem;
  line-height: 1.3;
  font-weight: 600;
  color: var(--ink);
  margin: 0 0 10px;
}

.results-page .card.quiet {
  background: transparent;
  border: 1px dashed #c4cbc5;
}

.results-page .card.quiet::before {
  display: none;
}

.results-page .card.quiet h3 {
  margin-bottom: 2px;
}
</style>

<style scoped>
.results-hero {
  max-width: 1140px;
  margin: 0 auto;
  padding: 8px 4px 4px;
  color: white;
  text-align: left;
}

.club {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: white;
  text-decoration: none;
  font-size: 0.9375rem;
  font-weight: 600;
  opacity: 0.92;
}

.club:hover {
  opacity: 1;
  color: white;
}

.club-logo {
  height: 40px;
  width: auto;
  border-radius: 3px;
}

h1 {
  margin: 18px 0 14px;
  font-size: clamp(2rem, 6vw, 3.25rem);
  line-height: 1.05;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.season {
  font-weight: 400;
  opacity: 0.85;
}

.seasons {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.seasons a {
  color: white;
  text-decoration: none;
  font-size: 0.875rem;
  padding: 3px 12px;
  border: 1px solid rgba(255, 255, 255, 0.45);
  border-radius: 999px;
}

.seasons a:hover {
  border-color: white;
}

.seasons a.active {
  background: white;
  color: var(--suit-black);
  border-color: white;
  font-weight: 600;
}

.status {
  color: var(--ink-muted);
}

.archive {
  margin: 8px 0 0;
  padding-top: 24px;
  border-top: 1px solid #d5dad5;
  color: var(--ink-muted);
  font-size: 0.9375rem;
}

.archive h2 {
  font-size: 1.375rem;
  font-weight: 600;
  color: var(--ink);
  margin: 0 0 12px;
}

.archive-seasons {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 12px;
  margin-bottom: 14px;
}

.archive-seasons a {
  padding: 6px 14px;
  background: white;
  border: 1px solid #e1e5e1;
  border-radius: 999px;
}

@media (max-width: 640px) {
  .results-page {
    padding: 0 8px;
  }

  .club-logo {
    height: 32px;
  }
}
</style>
