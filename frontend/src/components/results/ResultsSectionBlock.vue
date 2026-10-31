<template>
  <section class="band" :aria-labelledby="`section-${section.id}`">
    <h2 :id="`section-${section.id}`">{{ localName(language, section.title, section.title_en) }}</h2>

    <div class="cards">
      <results-slip v-for="child in section.children" :key="child.id" :section="child" />

      <template v-if="section.kind === 'bavlnka'">
        <article v-if="section.leaders?.players.length" class="card podium">
          <h3>{{ $t('results.podium') }}</h3>
          <ol>
            <li v-for="player in section.leaders.players" :key="player.first + player.last">
              <span class="medal" role="img" :aria-label="$t('results.place', { n: player.rank })">{{ MEDALS[player.rank - 1] }}</span>
              <span class="podium-name">{{ player.first }} <strong>{{ player.last.toLocaleUpperCase('cs-CZ') }}</strong></span>
              <span class="podium-points">{{ formatNumber(player.points) }}</span>
            </li>
          </ol>
          <div class="podium-foot">
            <router-link :to="{ name: 'bavlnka-ranking' }">{{ $t('results.wholeRanking') }}</router-link>
          </div>
        </article>
        <article v-for="month in months" :key="month.label" class="card">
          <h3>{{ month.label }}</h3>
          <div class="rows">
            <results-event-row v-for="event in month.events" :key="event.id" :event="event" />
          </div>
        </article>
      </template>

      <article v-else-if="section.kind !== 'group' && hasOwnContent" class="card" :class="{ wide: (section.events?.length ?? 0) > 4 }">
        <results-slip-content :section="section" />
      </article>

      <p v-if="section.kind === 'group' && !section.children.length && section.note" class="after">{{ section.note }}</p>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ResultsEvent, ResultsSection } from '@/api/ResultsApi';
import ResultsEventRow from '@/components/results/ResultsEventRow.vue';
import ResultsSlip from '@/components/results/ResultsSlip.vue';
import ResultsSlipContent from '@/components/results/ResultsSlipContent.vue';
import { computed } from 'vue';
import { formatNumber, language, monthName } from '@/i18n';
import { localName } from '@/i18n/names';

const props = defineProps<{ section: ResultsSection }>();

const MEDALS = ['🥇', '🥈', '🥉'];

const hasOwnContent = computed(() => props.section.kind !== 'events' || (props.section.events?.length ?? 0) > 0 || !!props.section.note);

const months = computed(() => {
  const groups: { label: string; events: ResultsEvent[] }[] = [];
  for (const event of props.section.events ?? []) {
    const label = event.date ? monthName(Number(event.date.slice(5, 7))) : '';
    let last = groups[groups.length - 1];
    if (last?.label !== label) groups.push(last = { label, events: [] });
    last.events.push(event);
  }
  return groups;
});
</script>

<style scoped>
.band {
  display: grid;
  grid-template-columns: 190px 1fr;
  gap: 28px;
  padding: 32px 0;
  border-top: 1px solid #d5dad5;
}

.band:first-child {
  border-top: none;
  padding-top: 8px;
}

/* The heading hangs in the margin and stays in view while its cards scroll past. */
h2 {
  position: sticky;
  top: 16px;
  align-self: start;
  margin: 0;
  font-size: 1.375rem;
  line-height: 1.25;
  font-weight: 600;
  color: var(--ink);
}

.cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(340px, 100%), 1fr));
  gap: 16px;
  align-items: start;
  min-width: 0;
}

.wide {
  grid-column: 1 / -1;
}

.wide :deep(.rows) {
  columns: 2 280px;
  column-gap: 28px;
}

.podium ol {
  list-style: none;
  margin: 0;
  padding: 0;
}

.podium li {
  display: grid;
  grid-template-columns: 1.6em 1fr auto;
  gap: 8px;
  align-items: baseline;
  padding: 7px 0;
  border-bottom: 1px solid var(--rule);
}

.podium li:last-child {
  border-bottom: none;
}

.medal {
  font-size: 1.125rem;
}

.podium-points {
  font-variant-numeric: tabular-nums;
  color: var(--ink-muted);
  font-size: 0.9375rem;
}

.podium-foot {
  margin-top: 12px;
  padding-top: 10px;
  border-top: 1px solid var(--rule);
  font-size: 0.9375rem;
}

.after {
  grid-column: 1 / -1;
  margin: 0;
  color: var(--ink-muted);
}

@media (max-width: 760px) {
  .band {
    grid-template-columns: 1fr;
    gap: 14px;
    padding: 24px 0;
  }

  h2 {
    position: static;
    font-size: 1.5rem;
  }
}
</style>
