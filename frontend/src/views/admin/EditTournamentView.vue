<template>
  <form @submit.prevent="submit">
    <div class="admin-head edit-head">
      <div>
        <h2>{{ title || 'Upravit turnaj' }}</h2>
        <div class="row-actions">
          <router-link :to="{ name: 'admin-tournament-show' }">zpět na skupinovky</router-link>
          <router-link v-if="slug" :to="{ name: 'tournament-results', params: { tournament: slug } }">zobrazit turnaj</router-link>
          <button type="button" class="link-button" @click="copyMatrikaToClipboard">kopírovat výsledky pro matriku</button>
        </div>
      </div>
    </div>

    <div class="admin-form edit-fields">
      <label>Název turnaje <input v-model="title" type="text" required size="40"></label>
      <label>Adresa (slug) <input v-model="slug" type="text" required size="30"></label>
    </div>

    <tournament-data-editor :tournamentData="(initialData as TournamentData)" @change="updateData">
    </tournament-data-editor>

    <div class="save-bar">
      <span v-if="message" :class="isError ? 'error' : 'muted'">{{ message }}</span>
      <button class="button primary" type="submit" :disabled="saving">{{ saving ? 'Ukládám…' : 'Uložit změny' }}</button>
    </div>
  </form>
</template>

<script setup lang="ts">
import TournamentApi from '@/api/TournamentApi';
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';

import TournamentDataEditor from '@/views/admin/TournamentDataEditor.vue';
import { Tournament, TournamentData } from '@/model/Tournament';
import { adminKey } from '@/utils/adminKey';

const title = ref("");
const slug = ref("");
const initialData = ref({});
const dataToSend = ref({});
const saving = ref(false);
const message = ref('');
const isError = ref(false);

const route = useRoute();

function updateData(newData: any) {
  dataToSend.value = newData;
}

onMounted(() => {
  // Always fresh: editing a cached copy and saving it would undo a recent save.
  TournamentApi.getTournament(route.params['tournament'] as string, true).then((tournament) => {
    title.value = tournament.name;
    slug.value = tournament.slug;
    initialData.value = tournament.data;
    dataToSend.value = tournament.data;
  }).catch((e) => {
    console.error(e);
    message.value = 'Turnaj se nepodařilo načíst';
    isError.value = true;
  });
});

function submit() {
  const id = Number.parseInt(route.params['tournament'] as string);
  saving.value = true;
  TournamentApi.updateTournament(id, title.value, slug.value, dataToSend.value, adminKey.value).then(() => {
    message.value = 'Uloženo v ' + new Date().toLocaleTimeString('cs-CZ', { hour: '2-digit', minute: '2-digit' });
    isError.value = false;
  }).catch((e) => {
    console.error(e);
    message.value = 'Chyba při ukládání turnaje';
    isError.value = true;
  }).finally(() => {
    saving.value = false;
  });
}

function copyMatrikaToClipboard() {
  const tournament = new Tournament(dataToSend.value as TournamentData);
  const text = tournament.toMatrikaString();
  navigator.clipboard.writeText(text).then(() => {
    window.alert((tournament.isFinished ? '' : 'POZOR: turnaj není dohrán. ')
      + 'Výsledky jsou zkopírované, vložte je do matriky jako CSV (Ctrl+V).');
  }).catch((e) => {
    console.error(e);
    window.alert('Chyba při kopírování matriky');
  });
}
</script>

<style scoped>
form {
  display: block;
}

.edit-fields {
  margin-bottom: 16px;
}

.save-bar {
  position: sticky;
  bottom: 0;
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 16px;
  padding: 10px 0;
  margin-top: 20px;
  background: white;
  border-top: 1px solid #d8dee4;
}
</style>
