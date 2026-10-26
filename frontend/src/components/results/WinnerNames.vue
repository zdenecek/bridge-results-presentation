<template>
  <span v-if="winner?.kind === 'text'" class="winner">{{ winner.text }}</span>
  <span v-else-if="winner" class="winner">
    <template v-for="(entry, i) in winner.entries" :key="i">
      <template v-if="i > 0"> a </template>
      <template v-if="'name' in entry">
        <strong>{{ entry.name }}</strong>
        <span v-if="entry.members" class="members">{{ entry.members }}</span>
      </template>
      <template v-else>
        <template v-for="(player, j) in entry.players" :key="j">
          <template v-if="j > 0"> - </template>
          {{ player.first }} <strong>{{ player.last.toLocaleUpperCase('cs-CZ') }}</strong>
        </template>
      </template>
    </template>
  </span>
</template>

<script setup lang="ts">
import { ResultsWinner } from '@/api/ResultsApi';

defineProps<{ winner: ResultsWinner | null | undefined }>();
</script>

<style scoped>
.winner {
  font-size: 1rem;
  line-height: 1.45;
}

.winner strong {
  font-weight: 600;
  letter-spacing: 0.02em;
}

.members {
  display: block;
  font-size: 0.875rem;
  color: var(--ink-muted);
}

</style>
