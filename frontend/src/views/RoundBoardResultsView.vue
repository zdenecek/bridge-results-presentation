<template>
  <div class="boards-page">
    <template v-if="tournament && round">
      <router-link :to="{ name: 'round-results', params: { round: round.number } }" class="back">{{ $t('tournament.backRound', { n: round.number }) }}</router-link>
      <div class="head">
        <h2 class="page-title">{{ $t('tournament.boardsOfRound', { n: round.number }) }}</h2>
        <button type="button" class="download" @click="download">{{ $t('tournament.downloadPbn') }}</button>
      </div>

      <BoardStepper :boards="boardList" :current="board"
                    :to="(b) => ({ name: 'round-board-results', params: { round: round!.number, board: b } })" />

      <RoundAllBoardsResults
        :round="round"
        :tournament="tournament"
        v-if="board === 'all'"
      />
      <RoundBoardResult
        :round="round"
        :tournament="tournament"
        :board="(board as number)"
        v-else
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import { useRoute } from "vue-router";

import { Ref, computed, inject } from "vue";
import { Tournament } from "@/model/Tournament";
import RoundBoardResult from "@/components/RoundBoardResult.vue";
import RoundAllBoardsResults from "@/components/RoundAllBoardsResults.vue";
import BoardStepper from "@/components/BoardStepper.vue";
import { downloadTextFile } from "@/utils/download";
import { createPBN } from "@/model/PBN";

const route = useRoute();

const tournament = inject("tournament") as Ref<Tournament | undefined>;
const round = computed(() =>
  tournament.value?.rounds.get(Number.parseInt(route.params["round"] as string))
);

const boards = computed(
  () => new Set(round.value?.boardResults?.map((r) => r.deal))
);

const board = (() => {
  if (route.params["board"] === "all") return "all";
  const board = Number.parseInt(route.params["board"] as string);
  if (!isNaN(board)) return board;
  return 1;
})();

const boardList = computed(() => [...boards.value].sort((a, b) => a - b));

function download() {
  const roundNumber = round.value?.number;
  if (!roundNumber) return;

   let name = (tournament.value?.title.replace(/\s/g, "-").normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase() + "-") ?? "";
   name += `${roundNumber}-boards.pbn`;
    downloadTextFile(
        name,
        createPBN(round.value)
    );
}
</script>

<style scoped>
.head {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  justify-content: space-between;
  gap: 4px 16px;
  margin-bottom: 10px;
}

.download {
  padding: 0;
  border: none;
  background: none;
  color: var(--felt);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}

.download:hover {
  color: var(--felt-deep);
  text-decoration: underline;
  text-underline-offset: 3px;
}
</style>

<style>
.boards-page .table-scroll {
  margin-top: 12px;
  padding: 6px 4px;
  border: 1px solid #e1e5e1;
  border-radius: 12px;
  background: white;
}
</style>
