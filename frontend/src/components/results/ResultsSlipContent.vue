<template>
  <template v-if="section.kind === 'tournament'">
    <tournament-leader v-if="section.tournament" :slug="section.tournament.slug" />
    <p v-else-if="section.note" class="note">{{ section.note }}</p>
  </template>

  <template v-else-if="section.kind === 'prebor'">
    <template v-if="section.prebor?.lastRound">
      <p class="lead">
        {{ $t(section.prebor.lastRound.winner.kind === 'pair' && section.prebor.lastRound.winner.entries.length > 1 ? 'results.roundWinnerPairs' : 'results.roundWinnerPair',
              { n: section.prebor.lastRound.number, date: formatDate(section.prebor.lastRound.date) }) }}
      </p>
      <p class="winner-line"><span class="trophy" aria-hidden="true">🏆</span><winner-names :winner="section.prebor.lastRound.winner" /></p>
      <div class="links">
        <a v-if="section.prebor.lastRound.url" :href="section.prebor.lastRound.url" target="_blank">{{ $t('results.roundResults') }}</a>
        <router-link :to="{ name: 'prebor-ranking', params: { slug: section.prebor.slug } }">{{ $t('results.standings') }}</router-link>
      </div>
    </template>
    <p v-else-if="section.note" class="note">{{ section.note }}</p>
    <p v-else-if="section.prebor" class="note">{{ $t('results.notStarted') }}</p>
  </template>

  <template v-else>
    <div v-if="section.events?.length" class="rows">
      <results-event-row v-for="event in section.events" :key="event.id" :event="event" />
    </div>
    <p v-else-if="section.note" class="note">{{ section.note }}</p>
  </template>
</template>

<script setup lang="ts">
import { ResultsSection } from '@/api/ResultsApi';
import ResultsEventRow from '@/components/results/ResultsEventRow.vue';
import TournamentLeader from '@/components/results/TournamentLeader.vue';
import WinnerNames from '@/components/results/WinnerNames.vue';
import { formatDate } from '@/i18n';

defineProps<{ section: ResultsSection }>();

</script>

<style scoped>
.lead {
  margin: 0;
  color: var(--ink-muted);
  font-size: 0.875rem;
}

.winner-line {
  margin: 2px 0 0;
}

.trophy {
  margin-right: 6px;
}

.links {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 20px;
  margin-top: 12px;
  padding-top: 10px;
  border-top: 1px solid var(--rule);
  font-size: 0.9375rem;
}

.note {
  margin: 0;
  color: var(--ink-muted);
}
</style>
