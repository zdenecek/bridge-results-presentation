<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { computed, reactive } from 'vue';
import TournamentGroupTotals from './TournamentGroupTotals.vue';
import { TableRoundResult } from '@/model/MatchResult';
import { Group } from '@/model/modelTypes';

const props = defineProps({
  tournament: {
    type: Object as () => Tournament,
    required: true,
  },
  groupIndex: {
    type: Number,
    required: true,
  },
  round: {
    type: Number,
    required: true,
  },
})


const group = reactive(props.tournament.groups[props.groupIndex] as Group);
const results = computed(() => props.tournament.getRoundResults(props.round)?.filter(r => group.players.includes(r.ns) || group.players.includes(r.ew)));

const wasPlayed = computed(() => props.tournament.getRound(props.round)?.hasMatchResults ?? false);

/** IMP and VP exist only on matches with a score; a match not played yet shows nothing. */
function score(result: TableRoundResult, key: 'imp_ns' | 'imp_ew' | 'vp_ns' | 'vp_ew'): number | undefined {
  return (result as Partial<Record<typeof key, number>>)[key];
}
</script>

<template>
  <div class="round-group">
    <template v-if="results.length && wasPlayed">
      <h4 class="sub">{{ $t('tournament.standingsAfter', { n: round }) }}</h4>
      <TournamentGroupTotals :tournament="tournament" :groupIndex="groupIndex" :untilRound="round" :showMatches="false" />

      <h4 class="sub">{{ $t('common.matches') }}</h4>
      <div class="table-scroll">
        <table class="table table-totals matches aligned">
          <tr>
            <th class="rank">{{ $t('common.table') }}</th>
            <th>{{ $t('common.pairs') }}</th>
            <th class="num imp">IMP</th>
            <th class="num vp">VP</th>
          </tr>
          <!-- A phone stacks the two pairs of a table, a computer puts them side by side. -->
          <tr v-for="result in results" :key="result.ns">
            <td class="rank">{{ result.table }}</td>
            <td class="col-name">
              <div class="pairs">
                <span v-for="key in (['ns', 'ew'] as const)" :key="key">
                  <router-link :to="{ name: 'pair-results', params: { pair: result[key] } }">
                    {{ tournament.getPair(result[key])?.title }}
                  </router-link>
                </span>
              </div>
            </td>
            <td class="num">
              <div v-if="score(result, 'imp_ns') !== undefined" class="scores">
                <span v-for="key in (['ns', 'ew'] as const)" :key="key">
                  <router-link v-if="result.status === 'played'"
                               :to="{ name: 'round-pair-results', params: { pair: result[key], round: props.round } }">
                    {{ score(result, `imp_${key}`) }}
                  </router-link>
                  <router-link v-else-if="result.status === 'postponed' && result.session" :title="$t('postponed.label')"
                               :to="{ name: 'postponed-session-pair', params: { session: result.session, pair: result[key] } }">
                    {{ score(result, `imp_${key}`) }}
                  </router-link>
                  <span v-else :title="$t('postponed.label')">{{ score(result, `imp_${key}`) }}</span>
                </span>
              </div>
            </td>
            <td class="num strong">
              <div v-if="score(result, 'vp_ns') !== undefined" class="scores">
                <span>{{ score(result, 'vp_ns') }}</span>
                <span>{{ score(result, 'vp_ew') }}</span>
              </div>
            </td>
          </tr>
        </table>
      </div>
    </template>
    <p v-else class="empty">{{ $t('tournament.resultsUnavailable') }}</p>
  </div>
</template>

<style scoped>
.sub {
  margin: 14px 8px 4px;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--ink-muted, #666);
}

.sub:first-child {
  margin-top: 0;
}

.pairs,
.scores {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.scores {
  align-items: flex-end;
}

@media (min-width: 641px) {
  .table.aligned th.imp {
    width: 6.5em;
  }

  .table.aligned th.vp {
    width: 9em;
  }

  .pairs {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: baseline;
    gap: 0 8px;
  }

  .pairs > :first-child {
    grid-column: 1;
    grid-row: 1;
  }

  .pairs > :last-child {
    grid-column: 3;
    grid-row: 1;
  }

  .pairs::before {
    content: '–';
    grid-column: 2;
    grid-row: 1;
    color: var(--ink-muted, #666);
  }

  .scores {
    flex-direction: row;
    justify-content: flex-end;
    gap: 0;
  }

  .scores > span + span::before {
    content: ':';
    padding: 0 4px;
    color: var(--ink-muted, #666);
    font-weight: 400;
  }
}

.empty {
  margin: 0 8px 8px;
  color: var(--ink-muted, #666);
}
</style>
