<template>
    <div>
      <h2>Seznam turnajů</h2>
      <div  class="flex">
      <div  v-for="tournament in tournaments" :key="tournament.slug">
      <router-link :to="{ name: 'admin-tournament-edit', params: { tournament: tournament.id } }">{{ tournament.name }} ({{  tournament.slug }})</router-link>
      <button class="delete" @click="deleteTournament(tournament.id)">Smazat</button>
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
  margin-left: 2em;
  border: none;
  border-radius: 5px;
  padding: 4px;
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

.flex {
  display: flex;
  align-items: center;
  flex-direction: column;
  gap: 10px;

}
</style>