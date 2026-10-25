<template>
  <div class="flex flex-column gap-small admin">
    <h2>Bavlnka – žebříček</h2>

    <form class="flex justify-center gap-small" @submit.prevent="load">
      <label for="bavlnka-key">Heslo</label>
      <input id="bavlnka-key" type="password" v-model="password" required>
      <button class="button" type="submit">Načíst</button>
    </form>

    <p v-if="message" :class="{ error: isError }">{{ message }}</p>

    <template v-if="data">
      <section>
        <h3>Aktualizace</h3>
        <p class="note">
          Načte nové turnaje ze stránek bkpraha.cz a stáhne jejich výsledky. Jeden běh zpracuje, co stihne
          za minutu; když zbývají další, spusť ho znovu. Na hostingu ho volá plánovaná úloha
          na adrese <code class="cron-url">{{ BavlnkaApi.cronUrl(data.cronKey) }}</code>
          (klíč v ní platí jen pro aktualizaci a změní se se změnou hesla).
        </p>
        <button class="button" :disabled="busy" @click="update">Aktualizovat</button>
        <div v-if="report" class="update-report">
          <div>Nově nalezené turnaje: {{ report.discovered.length }}</div>
          <div>Zpracované: {{ report.processed.map((p) => `${p.date} (${p.status})`).join(', ') || 'žádné' }}</div>
          <div>Zbývá zpracovat: {{ report.remaining }}</div>
          <div v-for="(e, i) in report.errors" :key="i" class="error">{{ e.source ?? e.url }}: {{ e.error }}</div>
        </div>
      </section>

      <section>
        <h3>Turnaje</h3>
        <form class="flex justify-center gap-small" @submit.prevent="addTournament">
          <input v-model="newUrl" type="url" placeholder="Adresa výsledků" required size="50">
          <input v-model="newDate" type="date" title="Datum, pokud ho nejde zjistit z výsledků">
          <button class="button" type="submit" :disabled="busy">Přidat</button>
        </form>
        <div class="table-scroll">
          <table class="table">
            <thead>
              <tr>
                <th>Datum</th>
                <th>Formát</th>
                <th>Dvojic</th>
                <th>Stav</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in data.tournaments" :key="t.id" :class="t.status">
                <td>
                  <a :href="t.results_url ?? t.source_url" target="_blank">{{ t.date ?? t.listed_date ?? '?' }}</a>
                </td>
                <td>{{ t.format ?? '' }}</td>
                <td>{{ t.pairs ?? '' }}</td>
                <td class="status">
                  {{ statusLabels[t.status] }}
                  <div v-if="t.error" class="error">{{ t.error }}</div>
                </td>
                <td class="actions">
                  <button class="button" :disabled="busy" @click="setStatus(t.id, 'pending')">Stáhnout znovu</button>
                  <button v-if="t.status !== 'ignored'" class="button" :disabled="busy" @click="setStatus(t.id, 'ignored')">Nepočítat</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section>
        <h3>Sloučená jména</h3>
        <p class="note">
          Jeden řádek na jméno: <code>jak je ve výsledcích =&gt; jak má být v žebříčku</code>.
          Jména lišící se jen diakritikou a samotné příjmení, které v žebříčku patří jedinému hráči,
          se slučují samy.
        </p>
        <textarea v-model="aliasText" rows="12" cols="70"></textarea>
        <div>
          <button class="button" :disabled="busy" @click="saveAliases">Uložit</button>
        </div>
      </section>

      <section>
        <h3>Hráči</h3>
        <p class="note">Hráči se samotným příjmením nebo s více zápisy jména jsou zvýrazněni.</p>
        <div class="table-scroll">
          <table class="table">
            <thead>
              <tr>
                <th>Hráč</th>
                <th>Zápisy ve výsledcích</th>
                <th>Matrika</th>
                <th>Turnajů</th>
                <th>Naposledy</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in data.players" :key="p.name" :class="{ suspicious: !p.name.includes(' ') || p.spellings.length > 1 }">
                <td class="col-name">{{ p.name }}</td>
                <td>{{ p.spellings.join(', ') }}</td>
                <td>{{ p.cbsIds.join(', ') }}</td>
                <td>{{ p.count }}</td>
                <td>{{ p.last }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import BavlnkaApi, { BavlnkaAdminData, BavlnkaUpdateReport } from '@/api/BavlnkaApi';
import { ref } from 'vue';

const password = ref('');
const data = ref<BavlnkaAdminData>();
const report = ref<BavlnkaUpdateReport>();
const aliasText = ref('');
const newUrl = ref('');
const newDate = ref('');
const busy = ref(false);
const message = ref('');
const isError = ref(false);

const statusLabels: Record<string, string> = {
  pending: 'čeká',
  ok: 'započítán',
  error: 'chyba',
  duplicate: 'duplicitní',
  ignored: 'nepočítá se',
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

function load() {
  return run(async () => {
    data.value = await BavlnkaApi.getAdminData(password.value);
    aliasText.value = Object.entries(data.value.aliases).map(([alias, name]) => `${alias} => ${name}`).join('\n');
  });
}

function update() {
  return run(async () => {
    report.value = await BavlnkaApi.update(password.value);
    data.value = await BavlnkaApi.getAdminData(password.value);
  });
}

function addTournament() {
  return run(async () => {
    await BavlnkaApi.addTournament(newUrl.value, newDate.value, password.value);
    newUrl.value = '';
    newDate.value = '';
    data.value = await BavlnkaApi.getAdminData(password.value);
  }, 'Turnaj přidán, stáhne se při příští aktualizaci.');
}

function setStatus(id: number, status: 'pending' | 'ignored') {
  return run(async () => {
    await BavlnkaApi.setTournamentStatus(id, status, password.value);
    data.value = await BavlnkaApi.getAdminData(password.value);
  });
}

function saveAliases() {
  const aliases: Record<string, string> = {};
  for (const line of aliasText.value.split('\n')) {
    const [alias, name] = line.split('=>').map((part) => part.trim());
    if (alias && name) aliases[alias] = name;
  }
  return run(async () => {
    await BavlnkaApi.setAliases(aliases, password.value);
    await load();
  }, 'Uloženo');
}
</script>

<style scoped>
.admin section {
  margin-top: 20px;
}

.note {
  color: #666;
  max-width: 700px;
}

.error {
  color: rgb(197, 25, 25);
}

.cron-url {
  word-break: break-all;
}

.update-report {
  margin-top: 10px;
  text-align: left;
}

tr.error td,
tr.pending td {
  background-color: #fff6e5;
}

tr.ignored td,
tr.duplicate td {
  color: #999;
}

tr.suspicious td {
  background-color: #fff6e5;
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
</style>
