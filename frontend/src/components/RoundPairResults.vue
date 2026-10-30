<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { unique } from '@/utils/unique';
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import Contract from './partial/ContractPartial.vue';
import BoardPartial from './partial/BoardPartial.vue';
import { Round } from '@/model/Round';
import { AdjustedBoardResult, PlayedBoardResult } from '@/model/BoardResult';
import { MatchResults } from '@/model/MatchResult';
import { useImpSort } from '@/utils/impSort';

const props = defineProps({
  tournament: {
    type: Object as () => Tournament,
    required: true,
  },
  round: {
    type: Object as () => Round,
    required: true,
  },
  pair: {
    type: Number,
    required: true,
  },
})


const results = computed(() => {
  const results = props.round.boardResults?.filter(r => r.ew === props.pair || r.ns === props.pair);
  if (results) results.sort((a, b) => a.deal - b.deal);
  return results;
})

const mergeNames = computed(() => {
  if (!results.value) return false;
  return unique(results.value.map(r => r.ns).values()).size === 1 && unique(results.value.map(r => r.ew).values()).size === 1
}

);
const finalResult = computed(() => {
  const results = props.tournament.getPairRoundResult(props.pair, props.round.number);
  if (!results) return undefined;
  if (results.length === 1) return results[0];
  return results.filter(r => r.tableResult.status === "played")[0];
});
const ns = computed(() => finalResult.value?.ns);

const imp_ns = computed(() => MatchResults.getImpsNS(finalResult.value?.tableResult));
const imp_ew = computed(() => MatchResults.getImpsEW(finalResult.value?.tableResult));
const vp_ns = computed(() => MatchResults.getVpsNS(finalResult.value?.tableResult));
const vp_ew = computed(() => MatchResults.getVpsEW(finalResult.value?.tableResult));

const averages = computed(() => props.round.hasAverages);

const { sorted, sortBy, indicator } = useImpSort(results, (r) => r.deal, (r) => {
  if (r.status === 'not-played') return undefined;
  return ns.value ? r.res_ns : r.res_ew;
});

/*
 * On a computer a click on a board opens its diagram in the column beside the score card;
 * a phone has no room for that and goes to the board's page.
 */
const router = useRouter();
const selected = ref<number>();
const selectedBoard = computed(() => selected.value === undefined ? undefined : props.round.boards?.get(selected.value));
const hasBoards = computed(() => (props.round.boards?.size ?? 0) > 0);

function open(deal: number) {
  if (!hasBoards.value) return;
  if (window.matchMedia('(min-width: 1000px)').matches) selected.value = selected.value === deal ? undefined : deal;
  else router.push({ name: 'round-board-results', params: { round: props.round.number, board: deal } });
}
</script>

<template>
  <div v-if="results" class="pair-round">
    <section v-if="mergeNames" class="card table-card summary">
      <div class="table-scroll">
        <table class="table">
          <tr>
            <th class="rank"></th>
            <th>Pár</th>
            <th class="num">IMP</th>
            <th class="num">VP</th>
          </tr>
          <tr v-for="line in (['ns', 'ew'] as const)" :key="line">
            <td class="rank">{{ line.toUpperCase() }}</td>
            <td class="col-name">
              <router-link
                :to="{ name: 'round-pair-results', params: { pair: results[0]?.[line], round: props.round.number } }">
                {{ tournament.getPair(results[0]?.[line]!)?.title }}
              </router-link>
            </td>
            <td class="num"><template v-if="imp_ns !== undefined">{{ line === 'ns' ? imp_ns : imp_ew }}</template></td>
            <td class="num strong"><template v-if="vp_ns !== undefined">{{ line === 'ns' ? vp_ns : vp_ew }}</template></td>
          </tr>
        </table>
      </div>
    </section>

    <div class="layout" :class="{ 'with-board': hasBoards }">
      <section class="card table-card">
        <div class="table-scroll">
          <table class="table table-results">
            <tr>
              <th class="sortable rank" title="Seřadit podle čísla rozdání" @click="sortBy('board')">
                Rozd. <span class="sort">{{ indicator('board') }}</span>
              </th>
              <template v-if="!mergeNames">
                <th>NS</th>
                <th>EW</th>
              </template>
              <th>Závazek</th>
              <th colspan="2">Výsledek</th>
              <th v-if="averages" class="num">Průměr</th>
              <th class="sortable num" title="Seřadit podle IMPů" @click="sortBy('imp')">
                IMP <span class="sort">{{ indicator('imp') }}</span>
              </th>
            </tr>
            <tr v-for="result in sorted" :key="result.deal" :class="{ selected: selected === result.deal, pickable: hasBoards }"
                @click="open(result.deal)">
              <td class="rank">
                <router-link :to="{ name: 'round-board-results', params: { round: round.number, board: result.deal } }" @click.stop>
                  {{ result.deal }}
                </router-link>
              </td>

              <template v-if="!mergeNames">
                <td class="col-name" v-for="line in (['ns', 'ew'] as const)" :key="line">
                  <router-link :to="{ name: 'round-pair-results', params: { pair: result[line], round: props.round.number } }" @click.stop>
                    {{ tournament.getPair(result[line])?.title }}
                  </router-link>
                </td>
              </template>
              <template v-if="result.status === 'played'">
                <td class="contract">
                  <Contract :contract="(result as PlayedBoardResult).contract"
                    :declarer="(result as PlayedBoardResult).declarer" />
                </td>
                <td>{{ (result as PlayedBoardResult).result }}</td>
                <td class="num">{{ (result as PlayedBoardResult).points }}</td>
                <td v-if="averages" class="num muted">{{ round.getBoardAverage(result.deal) }}</td>
                <td class="num strong">{{ ns ? (result as PlayedBoardResult).res_ns : (result as PlayedBoardResult).res_ew }}</td>
              </template>
              <template v-else-if="result.status === 'adjusted'">
                <td :colspan="averages ? 4 : 3">UV: {{ result.text ?? '' }}</td>
                <td class="num strong">{{ ns ? (result as AdjustedBoardResult).res_ns : (result as AdjustedBoardResult).res_ew }}</td>
              </template>
              <template v-else-if="result.status === 'not-played'">
                <td :colspan="averages ? 5 : 4" class="muted">Nehráno</td>
              </template>
            </tr>
          </table>
        </div>
      </section>

      <aside v-if="hasBoards" class="card board-card">
        <template v-if="selectedBoard && selected !== undefined">
          <div class="board-head">
            <h3>Rozdání {{ selected }}</h3>
            <router-link :to="{ name: 'round-board-results', params: { round: round.number, board: selected } }">
              Všechny výsledky rozdání ›
            </router-link>
          </div>
          <BoardPartial :key="selected" :board="selectedBoard" :number="selected" />
        </template>
        <p v-else class="hint">Klepněte na řádek a ukáže se tady rozdání.</p>
      </aside>
    </div>
  </div>
</template>

<style scoped>
.pair-round {
  display: flex;
  flex-direction: column;
  gap: 16px;
  max-width: 760px;
}

.summary {
  max-width: 520px;
}

.layout {
  display: grid;
  gap: 16px;
  align-items: start;
}

.board-card {
  display: none;
}

@media (min-width: 1000px) {
  .pair-round:has(.with-board) {
    max-width: none;
  }

  .layout.with-board {
    grid-template-columns: minmax(0, 640px) minmax(360px, 1fr);
  }

  .board-card {
    display: block;
    position: sticky;
    top: 80px;
  }
}

.board-head {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  justify-content: space-between;
  gap: 4px 12px;
  margin-bottom: 8px;
}

.board-head h3 {
  margin: 0;
}

.board-head a {
  font-size: 0.875rem;
}

.hint {
  margin: 0;
  color: var(--ink-muted);
}

.table tr.pickable {
  cursor: pointer;
}

.table tr.selected td {
  background: #e8f3e8;
}

.table .muted {
  color: var(--ink-muted);
}

.table .contract {
  white-space: nowrap;
}

.sortable {
  cursor: pointer;
  white-space: nowrap;
  user-select: none;
}

.sort {
  font-size: 0.7em;
  opacity: 0.6;
}
</style>
