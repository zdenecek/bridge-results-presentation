<template>
  <div class="flex flex-column gap-small admin">
    <h2>Přebory – celkové pořadí</h2>

    <form class="flex justify-center gap-small" @submit.prevent="load">
      <label for="prebor-key">Heslo</label>
      <input id="prebor-key" type="password" v-model="password" required>
      <button class="button" type="submit">Načíst</button>
    </form>

    <p v-if="message" :class="{ error: isError }">{{ message }}</p>

    <template v-if="data">
      <div class="flex justify-center gap-small">
        <button v-for="c in data.competitions" :key="c.id" class="button" :class="{ active: c.id === selectedId }"
                @click="select(c.id)">{{ c.title }}</button>
        <button class="button" :class="{ active: selectedId === null }" @click="select(null)">Nový přebor</button>
      </div>

      <section>
        <h3>{{ selectedId === null ? 'Nový přebor' : 'Nastavení přeboru' }}</h3>
        <form class="competition-form" @submit.prevent="saveCompetition">
          <label>Název <input v-model="form.title" required size="40"></label>
          <label>Sezóna <input v-model="form.season" required placeholder="2027/28" size="8"></label>
          <label>Adresa <input v-model="form.slug" required pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="top-2027" size="12"></label>
          <label>Jednotka <input v-model="form.unit" required placeholder="% nebo VP" size="4"></label>
          <label>Kol <input v-model.number="form.rounds" type="number" min="1" required></label>
          <label>Započítat nejlepších <input v-model.number="form.best" type="number" min="1" :max="form.rounds" required></label>
          <label class="wide">Tabulka přihlášek (CSV export)
            <input v-model="form.registration_csv" type="url" size="60" placeholder="https://docs.google.com/spreadsheets/d/…/export?format=csv">
          </label>
          <div><button class="button" type="submit" :disabled="busy">{{ selectedId === null ? 'Založit' : 'Uložit' }}</button></div>
        </form>
      </section>
    </template>

    <template v-if="data && current">

      <section>
        <h3>Kola</h3>
        <div class="table-scroll">
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
                  <a v-if="r.url" :href="r.url" target="_blank">{{ r.date ?? '?' }}</a>
                  <template v-else>{{ r.date ?? '?' }}</template>
                </td>
                <td>{{ r.source === 'manual' ? 'ručně' : 'TC' }}</td>
                <td>{{ r.pairs ?? '' }}</td>
                <td class="status">
                  {{ statusLabels[r.status] }}
                  <div v-if="r.error" class="error">{{ r.error }}</div>
                </td>
                <td class="actions">
                  <button v-if="r.source === 'tc'" class="button" :disabled="busy" @click="roundAction(r.id, 'refetch')">Stáhnout znovu</button>
                  <button v-if="r.status !== 'ignored'" class="button" :disabled="busy" @click="roundAction(r.id, 'ignore')">Nepočítat</button>
                  <button v-else class="button" :disabled="busy" @click="roundAction(r.id, 'restore')">Počítat</button>
                  <button class="button" :disabled="busy" @click="removeRound(r.id)">Smazat</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <h4>Přidat kolo z Tournament Calculatoru</h4>
        <form class="flex justify-center gap-small" @submit.prevent="addRound({ url: newUrl, date: newDate })">
          <input v-model="newUrl" type="url" placeholder="Adresa prezentace kola" required size="50">
          <input v-model="newDate" type="date" required title="Datum kola">
          <button class="button" type="submit" :disabled="busy">Přidat a stáhnout</button>
        </form>

        <h4>Přidat kolo s ručně zadanými výsledky</h4>
        <p class="note">
          Jeden řádek na dvojici, třeba zkopírovaný z tabulky: hráči oddělení pomlčkou
          (<code>Pavla Svobodová - Otakar Svoboda</code>) a skóre jako poslední číslo na řádku.
          Pořadí se dopočítá ze skóre.
        </p>
        <form class="flex flex-column gap-small" @submit.prevent="addRound({ url: manualUrl, date: manualDate, results: manualText })">
          <textarea v-model="manualText" rows="8" cols="70" required></textarea>
          <div class="flex justify-center gap-small">
            <input v-model="manualDate" type="date" required title="Datum kola">
            <input v-model="manualUrl" type="url" placeholder="Odkaz na výsledky (nepovinný)" size="40">
            <button class="button" type="submit" :disabled="busy">Přidat</button>
          </div>
        </form>
      </section>

      <section>
        <h3>Přihlášené dvojice</h3>
        <p class="note">
          Načtou se z <a v-if="current.registration_csv" :href="current.registration_csv" target="_blank">tabulky přihlášek</a><template v-else>tabulky přihlášek, kterou je potřeba zadat v nastavení</template>.
          Výsledek dvojice se připíše přihlášené dvojici, ve které hrají oba hráči, nebo té jediné,
          ve které hraje aspoň jeden z nich (náhradník).
        </p>
        <button class="button" :disabled="busy" @click="importEntries">Načíst z tabulky ({{ current.entries.length }})</button>
        <details v-if="current.entries.length">
          <summary>Seznam</summary>
          <ol class="entries">
            <li v-for="e in current.entries" :key="e.number" :value="e.number">{{ e.title }}</li>
          </ol>
        </details>
      </section>

      <section>
        <h3>Ke kontrole</h3>
        <p class="note">
          Výsledky dvojic, které nehrály v přihlášené sestavě. Výběrem se dá výsledek přiřadit jiné přihlášené
          dvojici nebo nechat jako samostatnou dvojici.
        </p>
        <p v-if="!current.review.length" class="note">Nic ke kontrole.</p>
        <div v-else class="table-scroll">
          <table class="table">
            <thead>
              <tr>
                <th>Datum</th>
                <th>Sestava</th>
                <th>Stav</th>
                <th>Počítá se pro</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in current.review" :key="row.round + row.playersKey" :class="row.kind">
                <td>{{ row.date }}</td>
                <td class="col-name">{{ row.lineup }}</td>
                <td>{{ kindLabels[row.kind] }}</td>
                <td>
                  <select :value="row.entry" :disabled="busy" @change="assign(row.round, row.playersKey, ($event.target as HTMLSelectElement).value)">
                    <option :value="0">samostatná dvojice</option>
                    <option v-for="e in current.entries" :key="e.number" :value="e.number">{{ e.number }}. {{ e.title }}</option>
                  </select>
                  <button v-if="row.kind === 'assigned' || row.kind === 'own'" class="button" :disabled="busy"
                          @click="assign(row.round, row.playersKey, '')">Zrušit</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section>
        <h3>Výstupy</h3>
        <p class="note">
          Pořadí: <router-link :to="{ name: 'prebor-ranking', params: { slug: current.slug } }">/prebor/{{ current.slug }}</router-link>,
          CSV pro widget na bkpraha.cz: <code class="url">{{ PreborApi.csvUrl(current.slug) }}</code>
        </p>
        <p class="note">
          Aktualizace stáhne znovu všechna kola všech přeborů z Tournament Calculatoru, aby se projevily pozdější opravy.
          Plánovaná úloha ji může volat na <code class="url">{{ PreborApi.cronUrl(data.cronKey) }}</code>.
        </p>
        <button class="button" :disabled="busy" @click="update">Stáhnout všechna kola znovu</button>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import PreborApi, { PreborAdminData, PreborCompetition, PreborNewRound, PreborResultKind } from '@/api/PreborApi';
import { computed, reactive, ref } from 'vue';

const password = ref('');
const data = ref<PreborAdminData>();
const selectedId = ref<number | null>(null);
const current = computed(() => data.value?.competitions.find((c) => c.id === selectedId.value));
const emptyForm: PreborCompetition = { slug: '', season: '', title: '', unit: '', best: 8, rounds: 10, registration_csv: '' };
const form = reactive<PreborCompetition>({ ...emptyForm });
const newUrl = ref('');
const newDate = ref('');
const manualText = ref('');
const manualDate = ref('');
const manualUrl = ref('');
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
  substitute: 's náhradníkem',
  assigned: 'přiřazeno ručně',
  own: 'samostatná (ručně)',
  conflict: 'hráči ze dvou dvojic',
  unregistered: 'nepřihlášená',
};

function errorText(e: any): string {
  if (e?.response?.status === 401) return 'Špatné heslo';
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
  const loaded = await PreborApi.getAdminData(password.value);
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
    await PreborApi.saveCompetition({ ...form }, selectedId.value, password.value);
    const slug = form.slug;
    await reload();
    select(data.value?.competitions.find((c) => c.slug === slug)?.id ?? null);
  }, 'Uloženo');
}

function load() {
  return run(reload);
}

function addRound(round: PreborNewRound) {
  return run(async () => {
    try {
      await PreborApi.addRound(current.value!.slug, round, password.value);
    } finally {
      await reload();
    }
    newUrl.value = newDate.value = manualText.value = manualDate.value = manualUrl.value = '';
  }, 'Kolo přidáno');
}

function roundAction(id: number, action: 'refetch' | 'ignore' | 'restore' | 'delete') {
  return run(async () => {
    await PreborApi.roundAction(id, action, password.value);
    await reload();
  });
}

function removeRound(id: number) {
  if (!confirm('Smazat kolo i s výsledky?')) return;
  return roundAction(id, 'delete');
}

function importEntries() {
  return run(async () => {
    const count = await PreborApi.importEntries(current.value!.slug, password.value);
    await reload();
    message.value = `Načteno ${count} dvojic`;
  });
}

function assign(round: number, playersKey: string, value: string) {
  return run(async () => {
    await PreborApi.setAssignment(round, playersKey, value === '' ? null : Number(value), password.value);
    await reload();
  });
}

function update() {
  return run(async () => {
    const report = await PreborApi.update(password.value);
    await reload();
    if (report.errors.length) throw new Error(report.errors.map((e) => `${e.url}: ${e.error}`).join('; '));
  }, 'Hotovo');
}
</script>

<style scoped>
.admin section {
  margin-top: 20px;
}

.admin .justify-center {
  flex-wrap: wrap;
}

.admin input {
  max-width: 100%;
}

.note {
  color: #666;
  max-width: 700px;
}

.error {
  color: rgb(197, 25, 25);
}

.url {
  word-break: break-all;
}

.button.active {
  font-weight: bold;
  text-decoration: underline;
}

tr.error td,
tr.pending td,
tr.conflict td {
  background-color: #fff6e5;
}

tr.ignored td {
  color: #999;
}

td.status .error {
  font-size: 0.8rem;
  max-width: 300px;
}

td.actions {
  white-space: nowrap;
}

textarea {
  max-width: 100%;
  font-family: monospace;
}

select {
  max-width: 260px;
}

.entries {
  text-align: left;
}

.competition-form {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px 16px;
  max-width: 700px;
  margin: 0 auto;
}

.competition-form input[type=number] {
  width: 4em;
}

.competition-form .wide {
  flex-basis: 100%;
}
</style>
