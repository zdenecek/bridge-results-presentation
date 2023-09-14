<template>
  <main-layout>
    <template #header>
      {{ title }}
    </template>
    <template #default>
      <router-view :key="$route.path" v-show="state === 'loaded'"></router-view>
      <span v-show="state === 'loading'">Načítám turnaj ...</span>
      <span v-show="state === 'error'">Chyba: {{ error }}</span>
    </template>

    <template #footer>


      <div class="flex flex-column legible" v-if="tournament?.td">
        <div>
          Vedoucí turnaje: {{ tournament.td.name }}
        </div>
        <div v-if="tournament.td.email">
          Email: <a :href="'mailto:' + tournament.td.email">{{ tournament.td.email }}</a>
        </div>
        <div v-if="tournament.td.phone">
          Tel.: <a :href="'tel:' + tournament.td.phone">{{ tournament.td.phone }}</a>
        </div>
        <div v-if="tournament.td.website">
          <a :href="tournament.td.website" target="_blank">Web</a>
        </div>
      </div>
    </template>
  </main-layout>
</template>

<script setup lang="ts">
import {   provide, ref, watch } from 'vue';
// eslint-disable-next-line
import TournamentApi from '@/api/TournamentApi';
import { Tournament } from '@/model/Tournament';
import { useRoute } from 'vue-router';
import MainLayout from '@/layouts/MainLayout.vue';
// eslint-disable-next-line
import { getData } from '@/getTestData';

const route = useRoute();
let title = ref('Výsledky turnaje');

let state = ref('loading');
let error = ref('');


let tournament = ref(undefined as Tournament | undefined)
provide('tournament', tournament);

watch(route, () => {
  loadTournament();
});
loadTournament();

function loadTournament() {
  // tournament.value = getData();
  // state.value = 'loaded';

  // return;


  TournamentApi.getTournament(route.params['tournament'] as string).then((data) => {
    state.value = 'loaded';
    tournament.value = new Tournament(data.data);
    title.value = data.name;
    document.title = data.name;
  }).catch((err) => {
    console.debug('Error loading tournament');
    console.error(err);
    state.value = 'error';

    if (err.response?.status === 404)
      error.value = 'Turnaj nebyl nalezen. Je možné, že nebyl vedoucím turnaje zveřejněn. Výsledky turnaje jsou zveřejňovány podle časových možností vedoucího turnaje.';
    else
      error.value = 'Nastala chyba při načítání turnaje';
  });
}



</script>

<style>
.legible {
  background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7));
  padding: 1em;
  gap: 4px;
}

.legible:hover {
  background-color: black;
}
</style>

