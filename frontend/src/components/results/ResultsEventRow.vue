<template>
  <component :is="event.url ? 'a' : 'div'" class="event" :class="{ linked: event.url, undated: !event.date }"
             :href="event.url ?? undefined" :target="event.url ? '_blank' : undefined"
             :title="event.url ? 'Zobrazit výsledky' : undefined">
    <time v-if="event.date" :datetime="event.date" class="date">{{ formatDate(event.date) }}</time>
    <span class="event-body">
      <span v-if="event.title" class="title">{{ event.title }}</span>
      <span v-if="event.winner" class="names">
        <span v-if="event.ongoing" class="leads">{{ leadsLabel }}</span>
        <span v-else class="trophy" role="img" aria-label="Vítěz">🏆</span>
        <winner-names :winner="event.winner" />
      </span>
      <span v-else-if="event.url" class="presentation">Prezentace</span>
    </span>
    <svg v-if="event.url" class="open" viewBox="0 0 16 16" aria-hidden="true">
      <path d="M6 3h7v7M13 3 4 12" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
  </component>
</template>

<script setup lang="ts">
import { ResultsEvent } from '@/api/ResultsApi';
import WinnerNames from '@/components/results/WinnerNames.vue';
import { computed } from 'vue';

const props = defineProps<{ event: ResultsEvent }>();

/** A competition still being played names who leads instead of a winner. */
const leadsLabel = computed(() => {
  const winner = props.event.winner;
  const several = winner && winner.kind !== 'text' && winner.entries.length > 1;
  if (winner?.kind === 'team') return several ? 'Vedou týmy' : 'Vede tým';
  return several ? 'Vedou dvojice' : 'Vede pár';
});

function formatDate(date: string): string {
  const [, month, day] = date.split('-').map(Number);
  return `${day}. ${month}.`;
}
</script>

<style scoped>
.event {
  display: grid;
  grid-template-columns: 3.4em 1fr 16px;
  gap: 10px;
  align-items: start;
  padding: 9px 8px;
  margin: 0 -8px;
  border-radius: 8px;
  border-bottom: 1px solid var(--rule);
  break-inside: avoid;
  color: var(--ink);
}

/* No date (a league, the cycles of a skupinovka): the names start where the dates would. */
.event.undated {
  grid-template-columns: 1fr 16px;
}

.event:last-child {
  border-bottom-color: transparent;
}

.results-page a.event,
.results-page a.event:hover {
  color: var(--ink);
  font-weight: 400;
  text-decoration: none;
}

.event.linked:hover {
  background: #f2f5f2;
}

.event.linked:hover .open {
  color: var(--felt);
}

.date {
  font-variant-numeric: tabular-nums;
  color: var(--ink-muted);
  font-size: 0.875rem;
  padding-top: 2px;
  white-space: nowrap;
}

.event-body {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.title {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--ink-muted);
}

.trophy,
.leads {
  margin-right: 6px;
}

.leads {
  color: var(--ink-muted);
}

.presentation {
  color: var(--felt);
  font-weight: 600;
}

.open {
  width: 14px;
  height: 14px;
  margin-top: 5px;
  color: #a3aba5;
}
</style>
