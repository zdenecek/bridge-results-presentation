<template>
  <div>
    <p class="page-lead">{{ $t('tournament.crossLead') }}</p>
    <div v-if="tournament" class="stack">
      <section v-for="group, i in tournament.groups" :key="i" class="card table-card cross-card">
        <h3>{{ $t('tournament.group', { name: group.name }) }}</h3>
        <TournamentGroupCrosstable :tournament="tournament" :groupIndex="i" />
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Tournament } from '@/model/Tournament';
import { Ref, inject } from 'vue';
import TournamentGroupCrosstable from '@/components/TournamentGroupCrosstable.vue';

const tournament = inject('tournament') as Ref<Tournament | undefined>;
</script>
