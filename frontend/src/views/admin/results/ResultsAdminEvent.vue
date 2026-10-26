<template>
  <tr :class="event.status">
    <template v-if="!editing">
      <td class="nowrap">{{ formatDate(event.date) }}</td>
      <td>
        <div v-if="event.title" class="muted">{{ event.title }}</div>
        <span v-if="event.ongoing" class="badge">probíhá</span>
        {{ winnerText(event.winner) || '–' }}
        <div v-if="event.winner_override" class="muted small">vítěz zadaný ručně</div>
        <div v-if="event.status === 'link' && !event.winner_override" class="muted small">výsledky nejde přečíst, vítěze je potřeba napsat</div>
        <div v-if="event.status === 'error'" class="error small">{{ event.error }}</div>
      </td>
      <td>
        <div class="row-actions">
          <a v-if="event.url" :href="event.url" target="_blank">výsledky</a>
          <button class="link-button" :disabled="admin.busy.value" @click="startEdit">upravit</button>
          <button v-if="event.url" class="link-button" :disabled="admin.busy.value" @click="refetch">stáhnout znovu</button>
          <button class="link-button danger" :disabled="admin.busy.value" @click="remove">smazat</button>
        </div>
      </td>
    </template>
    <td v-else colspan="3">
      <form class="admin-form" @submit.prevent="save">
        <label>Datum <input v-model="form.date" type="date"></label>
        <label class="grow">Název (nepovinný) <input v-model="form.title" placeholder="Hlavní párový turnaj"></label>
        <label class="wide">Vítěz napsaný ručně – přepíše vítěze přečteného z výsledků
          <input v-model="form.winner_override" :placeholder="winnerText(readWinner) || 'Jana ERDEOVÁ - Jiří EMMER'">
        </label>
        <label class="check"><input v-model="form.ongoing" type="checkbox"> soutěž ještě probíhá (ukáže „vede“ místo vítěze)</label>
        <label>Sekce
          <select v-model="form.section_id">
            <option v-for="s in eventSections" :key="s.id" :value="s.id">{{ s.title }}</option>
          </select>
        </label>
        <div class="row-actions">
          <button class="button primary" type="submit" :disabled="admin.busy.value">Uložit</button>
          <button type="button" class="link-button" @click="editing = false">zrušit</button>
        </div>
      </form>
    </td>
  </tr>
</template>

<script setup lang="ts">
import ResultsApi, { ResultsEvent, winnerText } from '@/api/ResultsApi';
import { adminKey } from '@/utils/adminKey';
import { allSections, resultsAdminKey } from '@/views/admin/results/resultsAdmin';
import { computed, inject, reactive, ref } from 'vue';

const props = defineProps<{ event: ResultsEvent; sectionId: number }>();
const admin = inject(resultsAdminKey)!;
const editing = ref(false);
const form = reactive({ date: '', title: '', winner_override: '', ongoing: false, section_id: 0 });

const eventSections = computed(() => allSections(admin.page.value).filter((s) => s.kind === 'events'));
// The winner the results page gave, shown as a hint while a hand-written one replaces it.
const readWinner = computed(() => props.event.winner?.kind === 'text' ? null : props.event.winner);

function formatDate(date: string | null): string {
  if (!date) return '?';
  const [year, month, day] = date.split('-').map(Number);
  return `${day}. ${month}. ${year}`;
}

function startEdit() {
  Object.assign(form, {
    date: props.event.date ?? '',
    title: props.event.title ?? '',
    winner_override: props.event.winner_override ?? '',
    ongoing: !!props.event.ongoing,
    section_id: props.sectionId,
  });
  editing.value = true;
}

async function save() {
  const ok = await admin.run(() => ResultsApi.updateEvent(props.event.id, {
    date: form.date || null,
    title: form.title || null,
    winner_override: form.winner_override || null,
    ongoing: form.ongoing,
    section_id: form.section_id,
  }, adminKey.value), 'Uloženo');
  if (ok) editing.value = false;
}

function refetch() {
  admin.run(() => ResultsApi.refetchEvent(props.event.id, adminKey.value),
    (result) => result?.status === 'ok' ? 'Výsledky staženy znovu' : `Stažení se nepovedlo: ${result?.error ?? ''}`);
}

function remove() {
  if (!confirm('Smazat turnaj ze stránky výsledků?')) return;
  admin.run(() => ResultsApi.deleteEvent(props.event.id, adminKey.value), 'Smazáno');
}
</script>

<style scoped>
.nowrap {
  white-space: nowrap;
}

.small {
  font-size: 0.8rem;
}

.badge {
  display: inline-block;
  margin-right: 6px;
  padding: 0 6px;
  border-radius: 4px;
  background: #e8f3e8;
  color: var(--primary-color);
  font-size: 0.8rem;
}

.grow {
  flex-grow: 1;
}

.check {
  flex-direction: row;
  align-items: center;
  gap: 6px;
}

tr.error td {
  background: #fff6e5;
}
</style>
