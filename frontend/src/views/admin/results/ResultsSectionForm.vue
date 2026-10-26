<template>
  <form class="admin-form section-form" @submit.prevent="save">
    <label>Název <input v-model="form.title" required size="34"></label>
    <label>Obsah
      <select v-model="form.kind">
        <option v-for="(label, kind) in KIND_LABELS" :key="kind" :value="kind" :disabled="kind === 'group' && form.parent_id !== null">{{ label }}</option>
      </select>
    </label>
    <label>Umístění
      <select v-model="form.parent_id" :disabled="form.kind === 'group'">
        <option :value="null">hlavní sekce</option>
        <option v-for="group in groups" :key="group.id" :value="group.id">pod „{{ group.title }}“</option>
      </select>
    </label>
    <label v-if="form.kind === 'tournament'">Skupinovka
      <select v-model="form.ref">
        <option :value="null">zatím žádná</option>
        <option v-for="t in admin.tournaments.value" :key="t.slug" :value="t.slug">{{ t.name }}</option>
      </select>
    </label>
    <label v-if="form.kind === 'prebor'">Přebor
      <select v-model="form.ref">
        <option :value="null">zatím žádný</option>
        <option v-for="c in admin.competitions.value" :key="c.slug" :value="c.slug">{{ c.title }}</option>
      </select>
    </label>
    <label class="wide">Poznámka, když ještě nejsou výsledky
      <input v-model="form.note" placeholder="Soutěž začíná 30. 11. 2026.">
    </label>
    <div class="form-actions">
      <button class="button primary" type="submit" :disabled="admin.busy.value">{{ section ? 'Uložit sekci' : 'Přidat sekci' }}</button>
      <button v-if="section" type="button" class="link-button" @click="emit('done')">zrušit</button>
    </div>
  </form>
</template>

<script setup lang="ts">
import ResultsApi, { ResultsSection, ResultsSectionInput } from '@/api/ResultsApi';
import { adminKey } from '@/utils/adminKey';
import { KIND_LABELS, resultsAdminKey } from '@/views/admin/results/resultsAdmin';
import { computed, inject, reactive, watch } from 'vue';

const props = defineProps<{ section?: ResultsSection; parentId?: number | null }>();
const emit = defineEmits<{ done: [] }>();
const admin = inject(resultsAdminKey)!;

const form = reactive<ResultsSectionInput>({
  season: admin.season.value,
  parent_id: props.section?.parent ?? props.parentId ?? null,
  title: props.section?.title ?? '',
  kind: props.section?.kind ?? 'events',
  ref: props.section?.ref ?? null,
  note: props.section?.note ?? null,
});

const groups = computed(() => (admin.page.value?.sections ?? []).filter((s) => s.kind === 'group' && s.id !== props.section?.id));

watch(() => form.kind, (kind) => {
  if (kind === 'group') form.parent_id = null;
  if (kind !== 'tournament' && kind !== 'prebor') form.ref = null;
});

async function save() {
  const ok = await admin.run(() => ResultsApi.saveSection({ ...form, season: admin.season.value }, props.section?.id ?? null, adminKey.value),
    props.section ? 'Sekce uložena' : 'Sekce přidána');
  if (ok) {
    if (!props.section) Object.assign(form, { title: '', ref: null, note: null });
    emit('done');
  }
}
</script>

<style scoped>
.section-form {
  padding: 12px 0 4px;
}

.form-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}
</style>
