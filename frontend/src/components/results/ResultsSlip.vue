<template>
  <article class="card" :class="{ wide: (section.events?.length ?? 0) > 4, quiet: onlyNote }">
    <h3>{{ section.title }}</h3>
    <results-slip-content :section="section" />
  </article>
</template>

<script setup lang="ts">
import { ResultsSection } from '@/api/ResultsApi';
import ResultsSlipContent from '@/components/results/ResultsSlipContent.vue';
import { computed } from 'vue';

const props = defineProps<{ section: ResultsSection }>();

/** Nothing played yet, only when it starts: shown as a placeholder, not a full card. */
const onlyNote = computed(() => {
  const s = props.section;
  if (s.kind === 'tournament') return !s.tournament;
  if (s.kind === 'prebor') return !s.prebor?.lastRound;
  return !s.events?.length;
});
</script>

<style scoped>
.wide {
  grid-column: 1 / -1;
}

.wide :deep(.rows) {
  columns: 2 280px;
  column-gap: 28px;
}
</style>
