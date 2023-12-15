<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { Ref, inject } from 'vue';

import TournamentGroupCrosstable from '@/components/TournamentGroupCrosstable.vue';
import TournamentGroupTotals from '@/components/TournamentGroupTotals.vue';
const tournament = inject('tournament') as Ref<Tournament | undefined>;
</script>

<template>
  <div class="flex flex-column justify-center align-center" v-if="tournament">
    <div v-for="group, i in tournament.groups" :key="i" class="flex flex-column gap-small print-no-break">
      <h2>Skupina {{ group.name }}</h2>

      <div class="flex align-start">
        <TournamentGroupTotals :show-cups="true" class="fix" :tournament="tournament" :groupIndex="i" />
        <TournamentGroupCrosstable :tournament="tournament" :groupIndex="i" />
      </div>

    </div>

  </div>
</template>

<style lang="scss">
.align-start {
  align-items: center;
}

// this is just so the table rows dont align 
.fix td {
  font-size: 1.2em;

  svg {
    width: 1.2em;
    height: 1.2em;
  }
}

.content .container {
  max-width: 100%;
}

@media print {

  .empty {
    background-color: unset !important;
  }

  .cup+a {
    font-weight: bold;
  }
}
</style>