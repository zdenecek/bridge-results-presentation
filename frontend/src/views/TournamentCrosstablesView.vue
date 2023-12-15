<template>
  <div>
    <h2>Křížové tabulky</h2>
    <div class="flex flex-column">

      <nav>
        <router-link :to="{ name: 'tournament-results' }">Celkové výsledky</router-link>
        <router-link :to="{ name: 'rounds-results' }">Výsledky kol</router-link>
      </nav>
      <template v-if="tournament">
        <div class="flex flex-column justify-center">
          <div v-for="group, i in tournament.groups" :key="i" class="flex flex-column">
            <h3>Skupina {{ group.name }}</h3>
            <TournamentGroupCrosstable :tournament="tournament" :groupIndex="i" />
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Tournament } from '@/model/Tournament';
import { Ref, inject } from 'vue';
import TournamentGroupCrosstable from '@/components/TournamentGroupCrosstable.vue';

const tournament = inject('tournament') as Ref<Tournament | undefined>;
</script>
