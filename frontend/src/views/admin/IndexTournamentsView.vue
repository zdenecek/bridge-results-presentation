<template>
    <div>
      <div  class="flex flex-column justify-center gap-small">
      <div class="flex justify-center gap-small"  v-for="tournament in tournaments" :key="tournament.slug">
      <router-link :to="{ name: 'admin-tournament-edit', params: { tournament: tournament.id } }">{{ tournament.name }} ({{  tournament.slug }})</router-link>
      <router-link class="nav-button" :to="{ name: 'tournament-results', params: { tournament: tournament.slug } }">Zobrazit</router-link>
      <router-link class="nav-button" :to="{ name: 'print-results', params: { tournament: tournament.slug } }">Tisk výsledků</router-link>
      <router-link class="nav-button" :to="{ name: 'seatings', params: { tournament: tournament.slug } }">Tisk nasazení</router-link>
      <button class="button delete" @click="deleteTournament(tournament.id)">Smazat</button>
      </div>

      </div>
    </div>
</template>

<script setup lang="ts">
import TournamentApi from '@/api/TournamentApi';
import { ref } from 'vue';
import TournamentEntry from '@/model/TournamentEntry';

const tournaments = ref(undefined as TournamentEntry[] | undefined);

TournamentApi.getTournaments().then((data) => {
  tournaments.value = data;
}).catch((e) => {
  console.error(e);
  console.debug('Error loading tournaments');
});

function deleteTournament(id: number) {
  const key = prompt('Heslo pro smazání turnaje');

  if (!key) {
    return;
  }

  TournamentApi.deleteTournament(id, key).then(() => {
    console.debug('Tournament deleted');
    tournaments.value = tournaments.value?.filter((t) => t.id !== id);
  }).catch((e) => {
    console.debug('Error deleting tournament');
    console.error(e);
  });

}

</script>

<style scoped>
.delete {
  background-color: rgb(197, 25, 25);
  color: white;
}

.delete:hover {
  background-color: rgb(255, 0, 0);
}

.delete:active {
  background-color: rgb(255, 0, 0);
}

.delete:focus {
  background-color: rgb(255, 0, 0);
}

</style>