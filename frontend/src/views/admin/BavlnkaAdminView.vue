<template>
  <div>
    <div class="admin-head">
      <h2>Bavlnka – žebříček</h2>
      <div class="row-actions">
        <router-link :to="{ name: 'bavlnka-ranking' }">veřejný žebříček</router-link>
        <button class="button primary" :disabled="busy" @click="update">{{ busy ? 'Pracuji…' : 'Aktualizovat' }}</button>
      </div>
    </div>

    <p v-if="message" :class="isError ? 'error' : 'muted'">{{ message }}</p>
    <p v-if="!data && !isError" class="muted">Načítám…</p>

    <template v-if="data">
      <div v-if="report" class="admin-card">
        <h3>Výsledek aktualizace</h3>
        <div>Nově nalezené turnaje: {{ report.discovered.length }}</div>
        <div>Zpracované: {{ report.processed.map((p) => `${p.date} (${p.status})`).join(', ') || 'žádné' }}</div>
        <div>Zbývá zpracovat: {{ report.remaining }}<template v-if="report.remaining"> – spusť aktualizaci znovu</template></div>
        <div v-for="(e, i) in report.errors" :key="i" class="error">{{ e.source ?? e.url }}: {{ e.error }}</div>
      </div>

      <section class="admin-card">
        <div class="admin-head">
          <h3>Turnaje</h3>
          <span class="muted">{{ countOk }} započítaných z {{ data.tournaments.length }}</span>
        </div>
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
                  <div v-if="t.error" class="error small">{{ t.error }}</div>
                </td>
                <td>
                  <div class="row-actions">
                    <button v-if="t.status !== 'ok'" class="link-button" :disabled="busy" @click="setStatus(t.id, 'pending')">stáhnout znovu</button>
                    <button v-if="t.status === 'ok'" class="link-button" :disabled="busy" @click="setStatus(t.id, 'ignored')">nepočítat</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <details>
          <summary>Přidat turnaj, který na bkpraha.cz chybí</summary>
          <form class="admin-form" @submit.prevent="addTournament">
            <label class="grow">Adresa výsledků <input v-model="newUrl" type="url" required size="50"></label>
            <label>Datum <input v-model="newDate" type="date" title="Pokud ho nejde zjistit z výsledků"></label>
            <button class="button" type="submit" :disabled="busy">Přidat</button>
          </form>
        </details>
      </section>

      <section class="admin-card">
        <h3>Sloučená jména</h3>
        <p class="muted">
          Jeden řádek na jméno: <code>jak je ve výsledcích =&gt; jak má být v žebříčku</code>.
          Jména lišící se jen diakritikou a samotné příjmení, které v žebříčku patří jedinému hráči,
          se slučují samy.
        </p>
        <textarea v-model="aliasText" rows="10"></textarea>
        <button class="button" :disabled="busy" @click="saveAliases">Uložit jména</button>
      </section>

      <section class="admin-card">
        <div class="admin-head">
          <h3>Hráči</h3>
          <span class="muted">{{ suspicious.length }} ke kontrole z {{ data.players.length }}</span>
        </div>
        <p class="muted">Ke kontrole jsou hráči se samotným příjmením nebo s více zápisy jména.</p>
        <label class="filter"><input v-model="onlySuspicious" type="checkbox"> jen ke kontrole</label>
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
              <tr v-for="p in shownPlayers" :key="p.name" :class="{ suspicious: isSuspicious(p) }">
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

      <details class="muted">
        <summary>Plánovaná úloha na hostingu</summary>
        <p>
          Každý den volá <code class="url">{{ BavlnkaApi.cronUrl(data.cronKey) }}</code>.
          Klíč v adrese platí jen pro aktualizaci a změní se se změnou hesla. Jeden běh zpracuje,
          co stihne za minutu.
        </p>
      </details>
    </template>
  </div>
</template>

<script setup lang="ts">
import BavlnkaApi, { BavlnkaAdminData, BavlnkaAdminPlayer, BavlnkaUpdateReport } from '@/api/BavlnkaApi';
import { adminKey } from '@/utils/adminKey';
import { computed, ref } from 'vue';

const data = ref<BavlnkaAdminData>();
const report = ref<BavlnkaUpdateReport>();
const aliasText = ref('');
const newUrl = ref('');
const newDate = ref('');
const busy = ref(false);
const message = ref('');
const isError = ref(false);
const onlySuspicious = ref(true);

const statusLabels: Record<string, string> = {
  pending: 'čeká',
  ok: 'započítán',
  error: 'chyba',
  duplicate: 'duplicitní',
  ignored: 'nepočítá se',
};

const isSuspicious = (p: BavlnkaAdminPlayer) => !p.name.includes(' ') || p.spellings.length > 1;
const countOk = computed(() => data.value?.tournaments.filter((t) => t.status === 'ok').length ?? 0);
const suspicious = computed(() => data.value?.players.filter(isSuspicious) ?? []);
const shownPlayers = computed(() => onlySuspicious.value ? suspicious.value : data.value?.players ?? []);

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
  data.value = await BavlnkaApi.getAdminData(adminKey.value);
  aliasText.value = Object.entries(data.value.aliases).map(([alias, name]) => `${alias} => ${name}`).join('\n');
}

function update() {
  return run(async () => {
    report.value = await BavlnkaApi.update(adminKey.value);
    await reload();
  });
}

function addTournament() {
  return run(async () => {
    await BavlnkaApi.addTournament(newUrl.value, newDate.value, adminKey.value);
    newUrl.value = '';
    newDate.value = '';
    await reload();
  }, 'Turnaj přidán, stáhne se při příští aktualizaci.');
}

function setStatus(id: number, status: 'pending' | 'ignored') {
  return run(async () => {
    await BavlnkaApi.setTournamentStatus(id, status, adminKey.value);
    await reload();
  }, status === 'pending' ? 'Turnaj se stáhne při příští aktualizaci.' : '');
}

function saveAliases() {
  const aliases: Record<string, string> = {};
  for (const line of aliasText.value.split('\n')) {
    const [alias, name] = line.split('=>').map((part) => part.trim());
    if (alias && name) aliases[alias] = name;
  }
  return run(async () => {
    await BavlnkaApi.setAliases(aliases, adminKey.value);
    await reload();
  }, 'Jména uložena');
}

run(reload);
</script>

<style scoped>
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

.filter {
  display: block;
  margin-bottom: 8px;
  font-size: 0.9rem;
}

tr.error td,
tr.pending td,
tr.suspicious td {
  background-color: #fff6e5;
}

tr.ignored td,
tr.duplicate td {
  color: #999;
}

.admin-card > .button {
  margin-top: 8px;
}
</style>
