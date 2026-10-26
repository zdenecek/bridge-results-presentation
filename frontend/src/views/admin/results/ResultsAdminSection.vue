<template>
  <section :class="nested ? 'nested' : 'admin-card'">
    <div class="section-head">
      <div>
        <component :is="nested ? 'h4' : 'h3'">{{ section.title }}</component>
        <span class="muted">{{ summary }}</span>
      </div>
      <div class="row-actions">
        <button class="link-button" :disabled="admin.busy.value || first" title="Posunout výš" @click="move(-1)">nahoru</button>
        <button class="link-button" :disabled="admin.busy.value || last" title="Posunout níž" @click="move(1)">dolů</button>
        <button class="link-button" :disabled="admin.busy.value" @click="editing = !editing">upravit</button>
        <button class="link-button danger" :disabled="admin.busy.value || !isEmpty" :title="isEmpty ? '' : 'Smazat jde jen prázdnou sekci'"
                @click="remove">smazat</button>
      </div>
    </div>

    <results-section-form v-if="editing" :section="section" @done="editing = false" />

    <template v-if="section.kind === 'events'">
      <div v-if="section.events?.length" class="table-scroll">
        <table class="table">
          <tbody>
            <results-admin-event v-for="event in section.events" :key="event.id" :event="event" :section-id="section.id" />
          </tbody>
        </table>
      </div>
      <details :open="!section.events?.length">
        <summary>Přidat turnaj</summary>
        <form class="admin-form" @submit.prevent="addEvent">
          <label class="wide">Odkaz na výsledky (Tournament Calculator, Giborg, i zkrácený 1url.cz)
            <input v-model="newEvent.url" type="url" required placeholder="https://…">
          </label>
          <label>Datum <input v-model="newEvent.date" type="date" title="Když chybí, vezme se ze složky výsledků"></label>
          <label class="grow">Název (nepovinný) <input v-model="newEvent.title" placeholder="Vedlejší párový turnaj"></label>
          <label class="check"><input v-model="newEvent.ongoing" type="checkbox"> soutěž ještě probíhá</label>
          <button class="button primary" type="submit" :disabled="admin.busy.value">Přidat a načíst vítěze</button>
        </form>
      </details>
    </template>

    <p v-else-if="section.kind === 'tournament'" class="muted">
      <template v-if="section.tournament">Ukazuje, kdo ve skupinovce {{ section.tournament.name }} vede a kdy je další kolo.</template>
      <template v-else-if="section.ref">Skupinovka „{{ section.ref }}“ neexistuje.</template>
      <template v-else>Skupinovka zatím není vybraná. {{ section.note ? `Stránka ukazuje: ${section.note}` : '' }}</template>
    </p>
    <p v-else-if="section.kind === 'prebor'" class="muted">
      <template v-if="section.prebor">Ukazuje vítěze posledního kola přeboru {{ section.prebor.title }} ({{ section.prebor.played }} z {{ section.prebor.rounds }} kol).</template>
      <template v-else>Přebor zatím není vybraný. {{ section.note ? `Stránka ukazuje: ${section.note}` : '' }}</template>
    </p>
    <p v-else-if="section.kind === 'bavlnka'" class="muted">
      Turnaje se berou samy z Bavlnky ({{ section.events?.length ?? 0 }} v této sezóně), spravují se na záložce Bavlnka.
    </p>

    <template v-if="section.kind === 'group'">
      <results-admin-section v-for="(child, i) in section.children" :key="child.id" :section="child" nested
                             :first="i === 0" :last="i === section.children.length - 1" />
      <details class="add-child">
        <summary>Přidat sekci pod „{{ section.title }}“</summary>
        <results-section-form :parent-id="section.id" />
      </details>
    </template>
  </section>
</template>

<script setup lang="ts">
import ResultsApi, { ResultsSection } from '@/api/ResultsApi';
import { adminKey } from '@/utils/adminKey';
import ResultsAdminEvent from '@/views/admin/results/ResultsAdminEvent.vue';
import ResultsSectionForm from '@/views/admin/results/ResultsSectionForm.vue';
import { KIND_LABELS, resultsAdminKey } from '@/views/admin/results/resultsAdmin';
import { computed, inject, reactive, ref } from 'vue';

const props = defineProps<{ section: ResultsSection; nested?: boolean; first?: boolean; last?: boolean }>();
const admin = inject(resultsAdminKey)!;
const editing = ref(false);
const newEvent = reactive({ url: '', date: '', title: '', ongoing: false });

const isEmpty = computed(() => !props.section.children.length && (props.section.kind !== 'events' || !props.section.events?.length));
const summary = computed(() => KIND_LABELS[props.section.kind]);

function move(direction: -1 | 1) {
  admin.run(() => ResultsApi.moveSection(props.section.id, direction, adminKey.value));
}

function remove() {
  if (!confirm(`Smazat sekci „${props.section.title}“?`)) return;
  admin.run(() => ResultsApi.deleteSection(props.section.id, adminKey.value), 'Sekce smazána');
}

async function addEvent() {
  const ok = await admin.run(() => ResultsApi.addEvent(props.section.id, {
    url: newEvent.url,
    date: newEvent.date || null,
    title: newEvent.title || null,
    ongoing: newEvent.ongoing,
  }, adminKey.value), (result) => {
    if (result?.status === 'ok') return 'Turnaj přidán, vítěz je načtený z výsledků.';
    if (result?.status === 'link') return 'Turnaj přidán, ale výsledky nejde přečíst – vítěze napiš přes „upravit“.';
    return `Turnaj přidán, ale výsledky se nepodařilo stáhnout: ${result?.error ?? ''}`;
  });
  if (ok) Object.assign(newEvent, { url: '', date: '', title: '', ongoing: false });
}
</script>

<style scoped>
.section-head {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: flex-start;
  gap: 6px 16px;
  margin-bottom: 8px;
}

.section-head h3,
.section-head h4 {
  margin: 0;
  display: inline;
  margin-right: 10px;
}

.section-head h4 {
  font-size: 1rem;
  color: #2c3e50;
}

.nested {
  margin-top: 14px;
  padding: 12px 0 4px 14px;
  border-left: 3px solid #d8dee4;
}

.add-child {
  margin-top: 14px;
}

.grow {
  flex-grow: 1;
}

.check {
  flex-direction: row;
  align-items: center;
  gap: 6px;
}

p.muted {
  margin: 4px 0;
}
</style>
