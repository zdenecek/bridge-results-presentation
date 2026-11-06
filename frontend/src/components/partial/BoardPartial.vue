<template>
  <div class="wrapper flex no-break">

    <div class="board">
      <BoardCardsPartial class="hand hand-n" :cards="board.deal[0]!" />
      <BoardCardsPartial class="hand hand-e" :cards="board.deal[1]!" />
      <BoardCardsPartial class="hand hand-s" :cards="board.deal[2]!" />
      <BoardCardsPartial class="hand hand-w" :cards="board.deal[3]!" />
      <div class="board-header">
        <span class="num">{{ number }}</span>
        <span class="sub"> {{ board.vul }} / {{ board.dealer }}</span>
      </div>
      <div class="board-center">
        <BoardCompass :dealer="board.dealer" :vul="board.vul" />
      </div>
      <div v-if="settings.showHcp" class="hcp" :aria-label="$t('settings.hcp')">
        <span class="hcp-n">{{ hcp[0] }}</span>
        <span class="hcp-e">{{ hcp[1] }}</span>
        <span class="hcp-s">{{ hcp[2] }}</span>
        <span class="hcp-w">{{ hcp[3] }}</span>
      </div>
      <DoubleDummyPartial v-if="board.ability" class="board-up-right" :dd="board.ability" />
      <div class="board-down-right">
        <MinimaxPartial v-if="board.minimax" :minimax="board.minimax" />
        <a :href="bridgesolverLink" target="_blank" class="bridgesolver-link">{{ $t('tournament.openSolver') }}</a>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import BoardCardsPartial from './BoardCardsPartial.vue';
import { Board } from '@/model/Board';
import DoubleDummyPartial from './DoubleDummyPartial.vue';
import MinimaxPartial from './MinimaxPartial.vue';
import { getBSHandviewerUrl } from '@/utils/handviewers';
import BoardCompass from './BoardCompass.vue';
import { settings } from '@/settings';
// eslint-disable-next-line
const props = defineProps({
  number: {
    type: Number,
    required: true,
  },
  board: {
    type: Object as () => Board,
    required: true,
  },
})


const POINTS: Record<string, number> = { A: 4, K: 3, Q: 2, J: 1 };
const hcp = computed(() => props.board.deal.map((hand) => [...hand].reduce((sum, card) => sum + (POINTS[card] ?? 0), 0)));

const bridgesolverLink = computed(() => getBSHandviewerUrl(props.board, props.number ));
  

</script>

<style scoped>
.no-break {
  page-break-inside: avoid;
}

.wrapper {
  flex-wrap: wrap;
  justify-content: center;
}

/* Sized by the cards, so every hand sits right by the compass. */
.board {
  display: grid;
  grid-template-columns: auto auto auto;
  justify-content: center;
  align-items: center;
  gap: 2px 6px;
}

.board-header {
  grid-row: 1;
  grid-column: 1;
  align-self: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.board-center {
  grid-row: 2;
  grid-column: 2;
  display: flex;
  align-items: center;
  justify-content: center;
}

.board-center .compass {
  width: 76px;
}

/* Points of each hand, laid out like the seats, in the empty corner by West and South. */
.hcp {
  grid-row: 3;
  grid-column: 1;
  align-self: end;
  justify-self: center;
  display: grid;
  grid-template-columns: repeat(3, 1.5em);
  grid-template-rows: repeat(3, 1.25em);
  place-items: center;
  font-size: 0.8125rem;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  color: var(--ink-muted, #5f6661);
}

.hcp-n { grid-row: 1; grid-column: 2; }
.hcp-e { grid-row: 2; grid-column: 3; }
.hcp-s { grid-row: 3; grid-column: 2; }
.hcp-w { grid-row: 2; grid-column: 1; }

/* The double dummy table and the minimax are notes beside the deal, kept small in the free corners. */
.board-up-right {
  grid-column: 3;
  grid-row: 1;
  align-self: start;
  justify-self: end;
  margin-left: 10px;
}

.board-down-right {
  grid-column: 3;
  grid-row: 3;
  align-self: end;
  justify-self: end;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
  margin-left: 10px;
  font-size: 0.75rem;
  color: var(--ink-muted, #5f6661);
}

.bridgesolver-link {
  text-decoration: none;
  color: var(--ink-muted, #5f6661);
  font-size: 0.7rem;
  font-weight: 400;
  opacity: 0.8;
}

.bridgesolver-link:hover {
  opacity: 1;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.num {
  font-size: 1.5rem;
  font-weight: bold;
  line-height: 1.1;
}

.sub {
  font-size: 0.75rem;
}

.hand {
  padding: 2px;
}

.hand-n {
  grid-column: 2;
  grid-row: 1;
  align-self: end;
  justify-self: center;
}

.hand-e {
  grid-column: 3;
  grid-row: 2;
  justify-self: start;
}

.hand-s {
  grid-column: 2;
  grid-row: 3;
  align-self: start;
  justify-self: center;
}

.hand-w {
  grid-column: 1;
  grid-row: 2;
  justify-self: end;
}
</style>
