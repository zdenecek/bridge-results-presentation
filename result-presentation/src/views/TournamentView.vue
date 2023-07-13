<template>
  <div>
    <div class="header">
      <div class="image">
        <h1>{{ title }}</h1>
      </div>

    </div>
    <div class="content">      
      <div class="container">
        <router-view :key="$route.path" v-show="state === 'loaded'"></router-view>
        <span v-show="state === 'loading'">Načítám turnaj ...</span>
        <span v-show="state === 'error'">Chyba: {{ error }}</span>
      </div>
    </div>
    <footer v-if="tournament?.td" class="flex flex-column">
        <div class="footer-wrapper">

          <div  class="flex flex-column legible">
            <div>
              Vedoucí turnaje:  {{ tournament.td.name }}
            </div>
            <div v-if="tournament.td.email">
              Email: <a :href="'mailto:' + tournament.td.email">{{ tournament.td.email }}</a>
            </div>
            <div v-if="tournament.td.phone">
              Tel.:  <a :href="'tel:'+tournament.td.phone">{{ tournament.td.phone }}</a> 
            </div>
            <div v-if="tournament.td.website">
              <a :href="tournament.td.website"  target="_blank">Web</a>
            </div>
          </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
// import { getData } from '@/getData';
import { provide, ref } from 'vue';
import TournamentApi from '@/api/TournamentApi';
import { Tournament } from '@/model/Tournament';
import { useRoute } from 'vue-router';

const route = useRoute();
let title = ref('Výsledky turnaje');

let state = ref('loading');
let error = ref('');


let tournament = ref(undefined as Tournament | undefined)
provide('tournament', tournament);


TournamentApi.getTournament(route.params['tournament'] as string).then((data) => {
  state.value = 'loaded';
  tournament.value = data;
  title.value = data.title + " - výsledky turnaje";
}).catch((err) => {
  console.debug('Error loading tournament');
  console.error(err);
  state.value = 'error';

  if (err.response?.status === 404)
    error.value = 'Turnaj nebyl nalezen. Je možné, že nebyl vedoucím turnaje zveřejněn. Výsledky turnaje jsou zveřejňovány podle časových možností vedoucího turnaje.';
  else
    error.value = 'Nastala chyba při načítání turnaje';
});

</script>

<style>

.header {
  background-color: #272727;
  color: white;

}

.header .image {
  padding: 15px;
  background: linear-gradient( rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4) ), url('@/assets/suits.webp') ;
  background-size: cover;
}

.content {
  padding: 30px 15px;
  background-color: white;
  padding-bottom: 80px;
  flex-grow: 3;
  max-width: 100vw;
}

.body {
  min-height: 100vh;
}

footer {
  background-color: #272727;
  color: lightgray;
}

.footer-wrapper {
  padding: 10px;

  background: linear-gradient( rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4) ), url('@/assets/suits.webp') ;
  background-size: cover;
  display: flex;
  justify-content: center;
}

.legible {
  background: linear-gradient( rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7) );
  padding: 1em;
  gap: 4px;
}

.legible:hover {
  background-color: black;
}
</style>

