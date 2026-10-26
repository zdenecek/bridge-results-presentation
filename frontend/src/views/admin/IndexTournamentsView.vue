<template>
  <div>
    <div class="admin-head">
      <h2>Turnaje</h2>
      <router-link class="button primary" :to="{ name: 'admin-tournament-create' }">Nový turnaj</router-link>
    </div>

    <p v-if="error" class="error">{{ error }}</p>
    <p v-else-if="!tournaments" class="muted">Načítám…</p>

    <div v-else class="table-scroll">
      <table class="table">
        <thead>
          <tr>
            <th>Turnaj</th>
            <th>Zobrazit</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="tournament in tournaments" :key="tournament.id">
            <td>
              <router-link :to="{ name: 'admin-tournament-edit', params: { tournament: tournament.id } }">{{ tournament.name }}</router-link>
              <div class="muted">{{ tournament.slug }}</div>
            </td>
            <td>
              <div class="row-actions">
                <router-link :to="{ name: 'tournament-results', params: { tournament: tournament.slug } }">výsledky</router-link>
                <router-link :to="{ name: 'print-results', params: { tournament: tournament.slug } }">tisk výsledků</router-link>
                <router-link :to="{ name: 'seatings', params: { tournament: tournament.slug } }">tisk nasazení</router-link>
              </div>
            </td>
            <td class="delete">
              <button class="link-button danger" @click="deleteTournament(tournament)">smazat</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import TournamentApi from '@/api/TournamentApi';
import { ref } from 'vue';
import TournamentEntry from '@/model/TournamentEntry';
import { adminKey } from '@/utils/adminKey';

const tournaments = ref<TournamentEntry[]>();
const error = ref('');

TournamentApi.getTournaments().then((data) => {
  tournaments.value = [...data].sort((a, b) => b.id - a.id);
}).catch((e) => {
  console.error(e);
  error.value = 'Turnaje se nepodařilo načíst';
});

function deleteTournament(tournament: TournamentEntry) {
  if (!confirm(`Opravdu smazat turnaj ${tournament.name}? Nejde to vrátit.`)) return;

  TournamentApi.deleteTournament(tournament.id, adminKey.value).then(() => {
    tournaments.value = tournaments.value?.filter((t) => t.id !== tournament.id);
  }).catch((e) => {
    console.error(e);
    error.value = 'Turnaj se nepodařilo smazat';
  });
}
</script>

<style scoped>
td.delete {
  text-align: right;
  font-size: 0.9rem;
}
</style>
