<template>
  <span v-if="!winner" class="unknown">vítěz není zadán</span>
  <span v-else-if="winner.kind === 'text'" class="winner">{{ winner.text }}</span>
  <span v-else class="winner">
    <template v-for="(entry, i) in winner.entries" :key="i">
      <template v-if="i > 0"> a </template>
      <strong v-if="'name' in entry">{{ entry.name }}</strong>
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

.unknown {
  color: var(--ink-muted);
}
</style>
