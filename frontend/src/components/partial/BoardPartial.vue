<template>
  <div class="wrapper flex">

    <div class="board">
      <BoardCardsPartial class="hand hand-n" :cards="board.deal[0]" />
      <BoardCardsPartial class="hand hand-e" :cards="board.deal[1]" />
      <BoardCardsPartial class="hand hand-s" :cards="board.deal[2]" />
      <BoardCardsPartial class="hand hand-w" :cards="board.deal[3]" />
      <div class="board-header">
        <span class="num">{{ number }}</span>
        <span class="sub"> {{ board.vul }} / {{ board.dealer }}</span>
      </div>
      <div class="board-center">
        <div class="indicator-n indicator"><span :class="{ 'vul': vulNS, 'dealer': board.dealer === 'N' }">N</span></div>
        <div class="indicator-E indicator"><span :class="{ 'vul': vulEW, 'dealer': board.dealer === 'E' }">E</span></div>
        <div class="indicator-S indicator"><span :class="{ 'vul': vulNS, 'dealer': board.dealer === 'S' }">S</span></div>
        <div class="indicator-W indicator"><span :class="{ 'vul': vulEW, 'dealer': board.dealer === 'W' }">W</span></div>
      </div>

    </div>
    <div class="board-down-right" v-if="board.ability && board.minimax">
      <DoubleDummyPartial :dd="board.ability" :minimax="board.minimax" />
    </div>
  </div>
</template>

<script setup lang="ts">

import BoardCardsPartial from './BoardCardsPartial.vue';
import { Board } from '@/model/Board';
import DoubleDummyPartial from './DoubleDummyPartial.vue';

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

const vulNS = props.board.vul === "NS" || props.board.vul === "All";
const vulEW = props.board.vul === "EW" || props.board.vul === "All";

</script>

<style scoped>
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
  display: grid;
  grid-template-rows: 1fr 1fr 1fr;
  grid-template-columns: 1fr 1fr 1fr 1fr;
}

.indicator {
  display: flex;
  justify-content: center;
  align-items: center;
}



.indicator span {
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
  width: 26px;
  color: white;

  background-color: #1a881a;
  border-radius: 50%;
  aspect-ratio: 1/1;
  font-weight: bold;
}

.indicator .vul {
  background-color: #ff4d4d;
}

.indicator span.dealer {

  border-radius: 10%;
  width: 20px;
}

.indicator-n {
  grid-column: 2 / span 2;
  grid-row: 1;

}

.indicator-E {
  grid-column: 3 / span 2;
  grid-row: 2;
}

.indicator-S {
  grid-column: 2 / span 2;
  grid-row: 3;
}

.indicator-W {
  grid-column: 1 / span 2;
  grid-row: 2;
}

.board-down-right {
  grid-column: 3;
  grid-row: 3;
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
</style>