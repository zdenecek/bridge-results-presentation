<template>
  <div>
    <div class="admin-head">
      <h2>Výsledky</h2>
      <div class="row-actions">
        <select v-model="season" aria-label="Sezóna">
          <option v-for="s in seasons" :key="s" :value="s">{{ seasonLabel(s) }}</option>
        </select>
        <router-link :to="season === current ? { name: 'home' } : { name: 'results', params: { season } }">veřejná stránka</router-link>
      </div>
    </div>

    <p v-if="message" :class="isError ? 'error' : 'muted'">{{ message }}</p>
    <p v-if="!page && !isError" class="muted">Načítám…</p>

    <template v-if="page">
      <section v-if="!page.sections.length" class="admin-card">
        <h3>Sezóna {{ seasonLabel(season) }} je prázdná</h3>
        <form class="admin-form import" @submit.prevent="importPage">
          <label class="grow">Načíst ze stránky výsledků na bkpraha.cz
            <input v-model="importUrl" type="url" required>
          </label>
          <button class="button primary" type="submit" :disabled="busy">{{ busy ? 'Načítám…' : 'Načíst' }}</button>
        </form>
        <p class="muted">
          Vezmou se sekce, turnaje i vítězové tak, jak jsou na stránce. Skupinovky a přebory z této aplikace se propojí,
          Bavlnky se vezmou ze záložky Bavlnka, pokud tam sezóna je. Načítání trvá až minutu.
        </p>
        <form v-if="otherSeasons.length" class="admin-form" @submit.prevent="copySeason">
          <label>Převzít sekce ze sezóny
            <select v-model="copyFrom">
              <option v-for="s in otherSeasons" :key="s" :value="s">{{ seasonLabel(s) }}</option>
            </select>
          </label>
          <button class="button primary" type="submit" :disabled="busy">Zkopírovat sekce</button>
        </form>
        <p class="muted">
          Zkopírují se jen nadpisy a sekce, bez turnajů. Skupinovky a přebory nové sezóny se pak vyberou
          v nastavení sekce. Nebo přidej první sekci níž.
        </p>
      </section>

      <results-admin-section v-for="(section, i) in page.sections" :key="section.id" :section="section"
                             :first="i === 0" :last="i === page.sections.length - 1" />

      <details class="admin-card" :open="!page.sections.length">
        <summary>Přidat hlavní sekci</summary>
        <results-section-form :parent-id="null" />
      </details>
    </template>
  </div>
</template>

<script setup lang="ts">
import PreborApi from '@/api/PreborApi';
import ResultsApi, { ResultsPage, ResultsWriteResult, currentSeason, seasonLabel } from '@/api/ResultsApi';
import TournamentApi from '@/api/TournamentApi';
import { adminKey } from '@/utils/adminKey';
import ResultsAdminSection from '@/views/admin/results/ResultsAdminSection.vue';
import ResultsSectionForm from '@/views/admin/results/ResultsSectionForm.vue';
import { resultsAdminKey } from '@/views/admin/results/resultsAdmin';
import { computed, provide, ref, watch } from 'vue';

const current = currentSeason();
const season = ref(current);
const page = ref<ResultsPage>();
const busy = ref(false);
const message = ref('');
const isError = ref(false);
const copyFrom = ref('');
const importUrl = ref('');
const tournaments = ref<{ slug: string; name: string }[]>([]);
const competitions = ref<{ slug: string; title: string }[]>([]);

/** Every season since the archive on bkpraha.cz starts (2022/23), up to the next one, so any can be filled. */
const seasons = computed(() => {
  const list: string[] = [];
  for (let year = Number(current.slice(0, 4)) + 1; year >= 2022; year--) {
    list.push(`${year}-${String((year + 1) % 100).padStart(2, '0')}`);
  }
  return [...new Set([...list, ...(page.value?.seasons ?? [])])].sort().reverse();
});
const otherSeasons = computed(() => (page.value?.seasons ?? []).filter((s) => s !== season.value));

function errorText(e: any): string {
  return e?.response?.data?.message ?? e?.message ?? String(e);
}

async function run(action: () => Promise<ResultsWriteResult>, success?: string | ((result: any) => string)): Promise<boolean> {
  busy.value = true;
  message.value = '';
  try {
    const response = await action();
    if (response.page) page.value = response.page;
    message.value = typeof success === 'function' ? success(response.result) : success ?? '';
    isError.value = false;
    return true;
  } catch (e) {
    console.error(e);
    message.value = errorText(e);
    isError.value = true;
    return false;
  } finally {
    busy.value = false;
  }
}

provide(resultsAdminKey, { season, page, busy, tournaments, competitions, run });

watch(season, async (s) => {
  message.value = '';
  isError.value = false;
  try {
    page.value = await ResultsApi.getAdminPage(s, adminKey.value);
    copyFrom.value = otherSeasons.value[0] ?? '';
    // The current season is on /vysledky/, past ones on the archive pages.
    importUrl.value = s === current ? 'https://www.bkpraha.cz/vysledky/' : `https://www.bkpraha.cz/archiv-vysledku-${s}/`;
  } catch (e) {
    console.error(e);
    message.value = errorText(e);
    isError.value = true;
  }
}, { immediate: true });

function importPage() {
  run(() => ResultsApi.importPage(season.value, importUrl.value, adminKey.value), (r) =>
    `Načteno ${r.sections} sekcí a ${r.events} turnajů. ` + (r.notFetched
      ? `U ${r.notFetched} turnajů se vítěz nestihl dočíst, dá se stáhnout přes „stáhnout znovu“.`
      : 'Hotovo.'));
}

function copySeason() {
  run(() => ResultsApi.copySeason(copyFrom.value, season.value, adminKey.value),
    (count) => `Zkopírováno sekcí: ${count}`);
}

TournamentApi.getTournaments().then((list) => {
  tournaments.value = [...list].sort((a, b) => b.id - a.id).map((t) => ({ slug: t.slug, name: t.name }));
}).catch(console.error);
PreborApi.getCompetitions().then((list) => {
  competitions.value = list.map((c) => ({ slug: c.slug, title: c.title }));
}).catch(console.error);
</script>

<style scoped>
.import {
  margin-bottom: 4px;
}

.grow {
  flex-grow: 1;
}

details.admin-card > summary {
  margin: 0;
}

details.admin-card[open] > summary {
  margin-bottom: 8px;
}
</style>
