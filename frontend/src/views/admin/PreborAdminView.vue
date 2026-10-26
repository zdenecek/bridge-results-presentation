<template>
  <div>
    <div class="admin-head">
      <h2>Přebory</h2>
      <div class="row-actions" v-if="data">
        <select v-model="selectedId" aria-label="Přebor" @change="select(selectedId)">
          <option v-for="c in data.competitions" :key="c.id" :value="c.id">{{ c.title }}</option>
          <option :value="null">+ Nový přebor…</option>
        </select>
      </div>
    </div>

    <p v-if="message" :class="isError ? 'error' : 'muted'">{{ message }}</p>
    <p v-if="!data && !isError" class="muted">Načítám…</p>

    <template v-if="data && current">
      <div class="row-actions summary">
        <span class="muted">Sezóna {{ current.season }} · odehráno {{ current.played }} z {{ current.rounds }} kol · počítá se {{ current.best }} nejlepších ({{ current.unit }})</span>
        <router-link :to="{ name: 'prebor-ranking', params: { slug: current.slug } }">veřejné pořadí</router-link>
      </div>

      <section v-if="review.length" class="admin-card" :class="{ attention: conflicts }">
        <div class="admin-head">
          <h3>Mimo přihlášené sestavy ({{ review.length }})</h3>
          <span v-if="conflicts" class="error">{{ conflicts }} ke rozhodnutí</span>
        </div>
        <p class="muted">
          Výsledky dvojic, které nehrály v přihlášené sestavě. Výběrem jde výsledek připsat jiné přihlášené dvojici
          nebo ho nechat jako samostatnou dvojici. Hráči ze dvou různých přihlášených dvojic zůstávají samostatnou
          dvojicí, dokud se nerozhodne jinak.
        </p>
        <div class="table-scroll">
          <table class="table">
            <thead>
              <tr>
                <th>Datum</th>
                <th>Sestava</th>
                <th>Proč</th>
                <th>Počítá se pro</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in review" :key="row.round + row.playersKey" :class="row.kind">
                <td class="nowrap">{{ formatDate(row.date) }}</td>
                <td class="col-name">{{ row.lineup }}</td>
                <td>{{ kindLabels[row.kind] }}</td>
                <td>
                  <select :value="row.entry" :disabled="busy" @change="assign(row.round, row.playersKey, ($event.target as HTMLSelectElement).value)">
                    <option :value="0">samostatná dvojice</option>
                    <option v-for="e in current.entries" :key="e.number" :value="e.number">{{ e.number }}. {{ e.title }}</option>
                  </select>
                  <button v-if="row.kind === 'assigned' || row.kind === 'own'" class="link-button" :disabled="busy"
                          @click="assign(row.round, row.playersKey, '')">vrátit automatiku</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="admin-card">
        <h3>Kola</h3>
        <p v-if="!current.roundList.length" class="muted">Zatím žádné kolo.</p>
        <div v-else class="table-scroll">
          <table class="table">
            <thead>
              <tr>
                <th>Kolo</th>
                <th>Datum</th>
                <th>Zdroj</th>
                <th>Dvojic</th>
                <th>Stav</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(r, i) in current.roundList" :key="r.id" :class="r.status">
                <td>{{ i + 1 }}.</td>
                <td>
                  <a v-if="r.url" :href="r.url" target="_blank" class="nowrap">{{ formatDate(r.date) }}</a>
                  <span v-else class="nowrap">{{ formatDate(r.date) }}</span>
                </td>
                <td>{{ r.source === 'manual' ? 'vloženo ručně' : 'Tournament Calculator' }}</td>
                <td>{{ r.pairs ?? '' }}</td>
                <td>
                  {{ statusLabels[r.status] }}
                  <div v-if="r.error" class="error small">{{ r.error }}</div>
                </td>
                <td>
                  <div class="row-actions">
                    <button v-if="r.source === 'tc'" class="link-button" :disabled="busy" @click="roundAction(r.id, 'refetch')">stáhnout znovu</button>
                    <button v-if="r.status !== 'ignored'" class="link-button" :disabled="busy" @click="roundAction(r.id, 'ignore')">nepočítat</button>
                    <button v-else class="link-button" :disabled="busy" @click="roundAction(r.id, 'restore')">počítat</button>
                    <button class="link-button danger" :disabled="busy" @click="removeRound(r.id)">smazat</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <details :open="!current.roundList.length">
          <summary>Přidat kolo</summary>
          <div class="mode">
            <label><input v-model="mode" type="radio" value="tc"> z Tournament Calculatoru</label>
            <label><input v-model="mode" type="radio" value="manual"> vložit výsledky ručně</label>
          </div>
          <form class="admin-form" @submit.prevent="addRound">
            <label>Datum kola <input v-model="newDate" type="date" required></label>
            <label class="grow">{{ mode === 'tc' ? 'Adresa prezentace kola' : 'Odkaz na výsledky (nepovinný)' }}
              <input v-model="newUrl" type="url" :required="mode === 'tc'" size="50">
            </label>
            <label v-if="mode === 'manual'" class="wide">
              Výsledky – jeden řádek na dvojici, třeba zkopírovaný z tabulky: hráči oddělení pomlčkou
              (<code>Pavla Svobodová - Otakar Svoboda</code>) a skóre jako poslední číslo na řádku
              <textarea v-model="manualText" rows="8" required></textarea>
            </label>
            <button class="button primary" type="submit" :disabled="busy">{{ mode === 'tc' ? 'Přidat a stáhnout' : 'Přidat' }}</button>
          </form>
        </details>
      </section>

      <section class="admin-card">
        <div class="admin-head">
          <h3>Přihlášené dvojice ({{ current.entries.length }})</h3>
          <button class="link-button" :disabled="busy || !current.registration_csv" @click="importEntries">načíst znovu z tabulky přihlášek</button>
        </div>
        <p class="muted">
          Výsledek se připíše přihlášené dvojici, ve které hrají oba hráči, nebo té jediné, ve které hraje
          aspoň jeden z nich (náhradník).
          <template v-if="!current.registration_csv">Tabulka přihlášek se zadává v nastavení.</template>
        </p>
        <details v-if="current.entries.length">
          <summary>Seznam dvojic</summary>
          <ol class="entries">
            <li v-for="e in current.entries" :key="e.number" :value="e.number">{{ e.title }}</li>
          </ol>
        </details>
      </section>

    </template>

    <details v-if="data" :key="selectedId ?? 'new'" class="admin-card" :open="selectedId === null">
      <summary>{{ selectedId === null ? 'Nový přebor' : 'Nastavení přeboru, CSV pro web a plánovaná úloha' }}</summary>
      <p v-if="selectedId === null" class="muted">Každá sezóna každého přeboru je samostatná soutěž s vlastními koly a přihláškami.</p>
      <form class="admin-form" @submit.prevent="saveCompetition">
        <label>Název <input v-model="form.title" required size="34"></label>
        <label>Sezóna <input v-model="form.season" required placeholder="2027/28" size="8"></label>
        <label>Adresa <input v-model="form.slug" required pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="top-2027" size="12"></label>
        <label>Jednotka <input v-model="form.unit" required placeholder="% nebo VP" size="5"></label>
        <label>Kol <input v-model.number="form.rounds" type="number" min="1" required class="number"></label>
        <label>Počítá se nejlepších <input v-model.number="form.best" type="number" min="1" :max="form.rounds" required class="number"></label>
        <label class="wide">Tabulka přihlášek (CSV export z Google Sheets)
          <input v-model="form.registration_csv" type="url" placeholder="https://docs.google.com/spreadsheets/d/…/export?format=csv">
        </label>
        <button class="button primary" type="submit" :disabled="busy">{{ selectedId === null ? 'Založit přebor' : 'Uložit nastavení' }}</button>
      </form>
      <template v-if="current">
        <p class="muted">
          CSV pro widget na bkpraha.cz: <code class="url">{{ PreborApi.csvUrl(current.slug) }}</code>
        </p>
        <p class="muted">
          Stažení všech kol všech přeborů znovu, aby se projevily pozdější opravy v Tournament Calculatoru.
          Plánovaná úloha ho může volat na <code class="url">{{ PreborApi.cronUrl(data.cronKey) }}</code>.
        </p>
        <button class="button" :disabled="busy" @click="update">Stáhnout všechna kola znovu</button>
      </template>
    </details>
  </div>
</template>

<script setup lang="ts">
import PreborApi, { PreborAdminData, PreborCompetition, PreborResultKind } from '@/api/PreborApi';
import { adminKey } from '@/utils/adminKey';
import { computed, reactive, ref } from 'vue';

const data = ref<PreborAdminData>();
const selectedId = ref<number | null>(null);
const current = computed(() => data.value?.competitions.find((c) => c.id === selectedId.value));
const review = computed(() => [...current.value?.review ?? []].sort((a, b) =>
  Number(b.kind === 'conflict') - Number(a.kind === 'conflict') || String(a.date).localeCompare(String(b.date))));
const conflicts = computed(() => review.value.filter((r) => r.kind === 'conflict').length);
const emptyForm: PreborCompetition = { slug: '', season: '', title: '', unit: '', best: 8, rounds: 10, registration_csv: '' };
const form = reactive<PreborCompetition>({ ...emptyForm });
const mode = ref<'tc' | 'manual'>('tc');
const newUrl = ref('');
const newDate = ref('');
const manualText = ref('');
const busy = ref(false);
const message = ref('');
const isError = ref(false);

const statusLabels: Record<string, string> = {
  pending: 'čeká',
  ok: 'započítáno',
  error: 'chyba',
  ignored: 'nepočítá se',
};

const kindLabels: Record<PreborResultKind, string> = {
  registered: 'přihlášená sestava',
  substitute: 'hrál náhradník',
  assigned: 'přiřazeno ručně',
  own: 'samostatná (ručně)',
  conflict: 'hráči ze dvou dvojic',
  unregistered: 'nepřihlášená dvojice',
};

function formatDate(date: string | null): string {
  if (!date) return '?';
  const [year, month, day] = date.split('-').map(Number);
  return `${day}. ${month}. ${year}`;
}

function errorText(e: any): string {
  return e?.response?.data?.message ?? e?.message ?? String(e);
}

async function run(action: () => Promise<void>, success = '') {
  busy.value = true;
  message.value = '';
  try {
    await action();
    message.value = success;
    isError.value = false;
  } catch (e) {
    console.error(e);
    message.value = errorText(e);
    isError.value = true;
  } finally {
    busy.value = false;
  }
}

async function reload() {
  const loaded = await PreborApi.getAdminData(adminKey.value);
  data.value = loaded;
  if (!current.value) selectedId.value = loaded.competitions[0]?.id ?? null;
  select(selectedId.value);
}

function select(id: number | null) {
  selectedId.value = id;
  const competition = current.value;
  Object.assign(form, competition
    ? { slug: competition.slug, season: competition.season, title: competition.title, unit: competition.unit,
        best: competition.best, rounds: competition.rounds, registration_csv: competition.registration_csv ?? '' }
    : emptyForm);
}

function saveCompetition() {
  return run(async () => {
    await PreborApi.saveCompetition({ ...form }, selectedId.value, adminKey.value);
    const slug = form.slug;
    await reload();
    select(data.value?.competitions.find((c) => c.slug === slug)?.id ?? null);
  }, 'Uloženo');
}

function addRound() {
  const round = mode.value === 'tc'
    ? { url: newUrl.value, date: newDate.value }
    : { url: newUrl.value, date: newDate.value, results: manualText.value };
  return run(async () => {
    try {
      await PreborApi.addRound(current.value!.slug, round, adminKey.value);
    } finally {
      await reload();
    }
    newUrl.value = newDate.value = manualText.value = '';
  }, 'Kolo přidáno');
}

function roundAction(id: number, action: 'refetch' | 'ignore' | 'restore' | 'delete') {
  return run(async () => {
    await PreborApi.roundAction(id, action, adminKey.value);
    await reload();
  }, action === 'refetch' ? 'Staženo znovu' : '');
}

function removeRound(id: number) {
  if (!confirm('Smazat kolo i s výsledky?')) return;
  return roundAction(id, 'delete');
}

function importEntries() {
  return run(async () => {
    const count = await PreborApi.importEntries(current.value!.slug, adminKey.value);
    await reload();
    message.value = `Načteno ${count} dvojic`;
  });
}

function assign(round: number, playersKey: string, value: string) {
  return run(async () => {
    await PreborApi.setAssignment(round, playersKey, value === '' ? null : Number(value), adminKey.value);
    await reload();
  });
}

function update() {
  return run(async () => {
    const report = await PreborApi.update(adminKey.value);
    await reload();
    if (report.errors.length) throw new Error(report.errors.map((e) => `${e.url}: ${e.error}`).join('; '));
  }, 'Hotovo');
}

run(reload);
</script>

<style scoped>
.summary {
  margin-bottom: 16px;
}

.attention {
  border-color: #f0b849;
  background: #fffaf0;
}

.url {
  word-break: break-all;
}

.small {
  font-size: 0.8rem;
  max-width: 300px;
}

.grow {
  flex-grow: 1;
}

.mode {
  display: flex;
  gap: 16px;
  margin-bottom: 10px;
}

.nowrap {
  white-space: nowrap;
}

tr.conflict td,
tr.error td,
tr.pending td {
  background-color: #fff6e5;
}

tr.ignored td {
  color: #999;
}

select {
  max-width: 280px;
}

.entries {
  columns: 2 280px;
}

.number {
  width: 5em;
}

details.admin-card > summary {
  margin: 0;
}

details.admin-card[open] > summary {
  margin-bottom: 12px;
}
</style>
