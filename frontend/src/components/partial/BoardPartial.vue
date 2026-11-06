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

    </div>
    <div class="board-down-right" >
      <DoubleDummyPartial v-if="board.ability && board.minimax" :dd="board.ability" :minimax="board.minimax" />
      <a :href="bridgesolverLink" target="_blank" class="bridgesolver-link">{{ $t('tournament.openSolver') }}</a>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import BoardCardsPartial from './BoardCardsPartial.vue';
import { Board } from '@/model/Board';
import DoubleDummyPartial from './DoubleDummyPartial.vue';
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

.board {
  display: grid;
  grid-template-columns: minmax(100px, 1fr) minmax(100px, 1fr) minmax(100px, 1fr);
}

.board-header {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.board-center {
  padding: 5px;
  grid-row: 2;
  grid-column: 2;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Points of each hand, laid out like the seats, in the empty corner by West and South. */
.hcp {
  grid-row: 3;
  grid-column: 1;
  align-self: end;
  justify-self: center;
  display: grid;
  grid-template-columns: repeat(3, 1.6em);
  grid-template-rows: repeat(3, 1.4em);
  place-items: center;
  margin-bottom: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  color: var(--ink-muted, #5f6661);
}

.hcp-n { grid-row: 1; grid-column: 2; }
.hcp-e { grid-row: 2; grid-column: 3; }
.hcp-s { grid-row: 3; grid-column: 2; }
.hcp-w { grid-row: 2; grid-column: 1; }

.board-down-right {
  grid-column: 3;
  grid-row: 3;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.bridgesolver-link {
  text-decoration: none;
  text-align: center;
  color: #000;
  font-size: 0.7rem;
}

.num {
  font-size: 1.5rem;
  font-weight: bold;
  text-align: center;
}

.sub {
  font-size: 0.8rem;
}

.hand {
  padding: 5px;
  margin: auto;
}

.hand-n {
  grid-column: 2;
  grid-row: 1;
}

.hand-e {
  grid-column: 3;
  grid-row: 2;
}

.hand-s {
  grid-column: 2;
  grid-row: 3;
}

.hand-w {
  grid-column: 1;
  grid-row: 2;
}

/* On a phone the double dummy table takes the empty corner beside South instead of a row of its own. */
@media (max-width: 600px) {
  .wrapper {
    display: grid;
    grid-template-columns: auto minmax(96px, auto) 1fr;
    align-items: center;
  }

  .board {
    display: contents;
  }

  .board-down-right {
    align-self: end;
    gap: 6px;
    font-size: 0.875rem;
  }

  .board-down-right :deep(th) {
    padding: 3px 2px;
  }

  .board-down-right :deep(td) {
    width: auto;
    padding: 3px 4px;
  }

  .board-down-right :deep(td:first-child) {
    padding-right: 4px;
  }
}
</style>