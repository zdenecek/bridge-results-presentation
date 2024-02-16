<template>
       <div>
        <h2>Upravit turnaj</h2>
         <nav>
           <router-link v-if="slug" :to="{ name: 'tournament-results', params: { tournament: slug } }">Zobrazit turnaj</router-link>
           <button class="nav-button button" @click="copyMatrikaToClipboard">Kopírovat výsledky pro matriku</button>
          </nav>
        <form @submit.prevent="submit">
        <div class="fields">
        <label for="title">Název turnaje</label>
        <input id="title" type="text" v-model="title" required>
        <label for="title">slug (název v url adrese)</label>
        <input id="title" type="text" v-model="slug" required>
        </div>
        <tournament-data-editor :tournamentData="(initialData as TournamentData)" @change="updateData">
        </tournament-data-editor>
        
        <div class="fields">
          <label for="key">Heslo</label>
          <input id="key" type="password" v-model="password" required>
        </div>
        <input type="submit">
      </form>
    </div>
</template>

<script setup lang="ts">
import TournamentApi from '@/api/TournamentApi';
import { onMounted, ref  } from 'vue';
import {  useRoute } from 'vue-router';

import TournamentDataEditor from '@/views/admin/TournamentDataEditor.vue';
import { Tournament, TournamentData } from '@/model/Tournament';

const title = ref("");
const slug = ref("");
const password = ref("");
const initialData = ref({});
const dataToSend = ref({});

const route = useRoute();

function updateData(newData: any) {
  dataToSend.value = newData;
}

onMounted(() => {
  TournamentApi.getTournament(route.params['tournament'] as string).then((tournament) => {
    title.value = tournament.name;
    slug.value = tournament.slug;
    initialData.value = tournament.data;
    dataToSend.value = tournament.data;
  }).catch((e) => {
    console.error(e);
    console.debug('Error loading tournament');
  });
});

function submit() {
    const id = Number.parseInt(route.params['tournament'] as string);
  TournamentApi.updateTournament( id, title.value, slug.value, dataToSend.value, password.value).then(() => {
      console.debug('Tournament edited');
      window.alert('Turnaj upraven');

  }).catch((e) => {
    console.debug('Error creating tournament');
    console.error(e);
    window.alert('Chyba při ukládání turnaje')
  });
}

function copyMatrikaToClipboard() {
  const tournament = new Tournament(dataToSend.value as TournamentData);
  const text = tournament.toMatrikaString();
  navigator.clipboard.writeText(text).then(() => {
    console.debug('Matrika copied');
    window.alert('Matrika zkopírována, vložte jako csv do matriky ctrl+v');
  }).catch((e) => {
    console.debug('Error copying matrika');
    console.error(e);
    window.alert('Chyba při kopírování matriky');
  });
}
</script>