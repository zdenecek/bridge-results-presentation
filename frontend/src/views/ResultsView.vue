<template>
  <main-layout>
    <template #hero>
      <div class="results-hero">
        <h1>{{ $t('results.title') }} <span class="season">{{ seasonLabel(season) }}</span></h1>
        <div v-if="page && season !== current" class="seasons" role="navigation" :aria-label="$t('results.seasons')">
          <router-link v-for="s in page.seasons" :key="s" :to="s === current ? { name: 'home' } : { name: 'results', params: { season: s } }"
                       :class="{ active: s === season }" :aria-current="s === season ? 'page' : undefined">{{ seasonLabel(s) }}</router-link>
        </div>
      </div>
    </template>
    <template #default>
      <div class="results-page">
        <p v-if="state === 'loading'" class="status">{{ $t('results.loading') }}</p>
        <p v-else-if="state === 'error'" class="status">{{ $t('results.error') }}</p>
        <p v-else-if="page && !page.sections.length" class="status">{{ $t('results.empty', { season: seasonLabel(season) }) }}</p>

        <results-section-block v-for="section in sections" :key="section.id" :section="section" />

        <section v-if="page" class="archive" aria-labelledby="archive-title">
          <h2 id="archive-title">{{ $t('results.archive') }}</h2>
          <div v-if="pastSeasons.length" class="archive-seasons">
            <router-link v-for="s in pastSeasons" :key="s" :to="{ name: 'results', params: { season: s } }">{{ $t('results.season', { season: seasonLabel(s) }) }}</router-link>
          </div>
          <i18n-t keypath="results.allTournaments" tag="p">
            <template #link>
              <router-link :to="{ name: 'tournaments' }">{{ $t('results.allTournamentsLink') }}</router-link>
            </template>
          </i18n-t>
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
import { t as tr } from '@/i18n';
import { localName } from '@/i18n/names';

const route = useRoute();
const current = currentSeason();
const season = computed(() => String(route.params.season || current));
const page = ref<ResultsPage>();
const state = ref<'loading' | 'loaded' | 'error'>('loading');
const pastSeasons = computed(() => (page.value?.seasons ?? []).filter((s) => s !== season.value && s < current));

/**
 * A closed season reads in date order. In the season being played what is running now comes first,
 * gathered under one heading, then the rest, each with its latest tournament first.
 */
const sections = computed(() => {
  const list = page.value?.sections ?? [];
  if (season.value !== current) return list;
  const newestFirst = (section: ResultsSection): ResultsSection => ({
    ...section,
    events: section.events ? [...section.events].reverse() : section.events,
    children: section.children.map(newestFirst),
  });

  const running: ResultsSection[] = [];
  const series: ResultsSection[] = [];
  const rest: ResultsSection[] = [];
  for (const section of list.map(newestFirst)) {
    if (section.kind === 'bavlnka' && section.state === 'running') {
      series.push(section);
    } else if (section.kind === 'group') {
      // A cycle of a skupinovka is named with it: "Skupinovka A – podzim".
      running.push(...section.children.filter((c) => c.state === 'running').map((c) => ({
        ...c,
        title: `${section.title} – ${c.title.includes(' ') ? c.title : c.title.toLocaleLowerCase('cs-CZ')}`,
        title_en: `${localName('en', section.title, section.title_en)} – ${localName('en', c.title, c.title_en)}`,
      })));
      const others = section.children.filter((c) => c.state !== 'running');
      if (others.length) rest.push({ ...section, children: others });
    } else if (section.state === 'running') {
      running.push(section);
    } else {
      rest.push(section);
    }
  }
  const now: ResultsSection[] = running.length
    ? [{ id: -1, parent: null, title: tr('results.playing'), title_en: tr('results.playing'), kind: 'group', ref: null, note: null, state: 'running', children: running }]
    : [];
  return [...now, ...series, ...rest];
});

watch(season, (s) => {
  state.value = 'loading';
  document.title = tr('results.pageTitle', { season: seasonLabel(s) });
  ResultsApi.getPage(s).then((data) => {
    page.value = data;
    state.value = 'loaded';
  }).catch((e) => {
    console.error(e);
    state.value = 'error';
  });
}, { immediate: true });
</script>


<style scoped>
.results-hero {
  max-width: 1140px;
  margin: 0 auto;
  padding: 8px 4px 4px;
  color: white;
  text-align: left;
}


h1 {
  margin: 10px 0 14px;
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
}
</style>
