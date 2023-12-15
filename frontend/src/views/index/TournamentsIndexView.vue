<template>
  <main-layout>
    <template #header>
      Výsledky turnajů Bridžového klubu Praha
    </template>
    <template #default>

      <h2>Seznam turnajů</h2>
      <div class="flex flex-column gap-small">
      <div v-for="tournament in tournaments" :key="tournament.slug">
        <router-link :to="{ name: 'tournament-results', params: { tournament: tournament.slug } }">{{ tournament.name }}</router-link>
      </div>
      </div>
      
    </template>
    
    <template #footer>

    </template>
  </main-layout>
</template>

<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
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

</script>
