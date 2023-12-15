<template>
  <div>
    <h2>Celkové výsledky</h2>
    <div class="flex flex-column">

      <nav>
        <router-link :to="{ name: 'seatings' }" v-if="!tournament?.isFinished">Nasazení</router-link>
        <router-link :to="{ name: 'rounds-results' }">Výsledky kol</router-link>
        <router-link :to="{ name: 'crosstables' }">Křížové tabulky</router-link>
      </nav>
      <template v-if="tournament">
        <TournamentTotals :tournament="tournament" />
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Tournament } from '@/model/Tournament';
import { Ref, inject } from 'vue';
import TournamentTotals from '@/components/TournamentTotals.vue';

const tournament = inject('tournament') as Ref<Tournament | undefined>;

</script>
