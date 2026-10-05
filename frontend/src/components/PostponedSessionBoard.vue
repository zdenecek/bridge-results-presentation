<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { computed } from 'vue';
import Contract from './partial/ContractPartial.vue';
import BoardPartial from './partial/BoardPartial.vue';
import { PostponedSession } from '@/model/PostponedSession';

const props = defineProps({
  tournament: {
    type: Object as () => Tournament,
    required: true,
  },
  session: {
    type: Object as () => PostponedSession,
    required: true,
  },
  board: {
    type: Number,
    required: true,
  },
})

const lines = computed(() => props.session.getBoardLines(props.board).map((line) => {
  const match = line.match === undefined ? undefined : props.session.data.matches[line.match];
  return { ...line, seating: match ? props.tournament.getPostponedSeating(match) : undefined };
}));

const average = computed(() => props.session.averages.get(props.board));
const boardData = computed(() => props.session.boards.get(props.board));

</script>

<template>
  <div class="flex flex-column justify-center">
    <h3>Rozdání číslo {{ props.board }}</h3>

    <div class="flex flex-column justify-center">
      <BoardPartial v-if="boardData" :board="boardData" :number="board" />

      <div class="table-scroll">
        <table class="table table-results">
          <tr>
            <th>NS</th>
            <th>EW</th>
            <th>Závazek</th>
            <th colspan="2">Výsledek</th>
            <th>IMP</th>
          </tr>
          <tr v-for="(line, index) in lines" :key="index" :class="{ jack: !line.seating }">
            <template v-if="line.seating">
              <td class="col-name" v-for="seat in (['ns', 'ew'] as const)" :key="seat">
                <router-link v-if="line.seating[seat]"
                  :to="{ name: 'pair-results', params: { pair: line.seating[seat] } }">
                  {{ tournament.getPair(line.seating[seat]!)?.title }}
                </router-link>
              </td>
            </template>
            <td v-else colspan="2">Jack</td>
            <td>
              <Contract :contract="line.contract" :declarer="line.declarer" />
            </td>
            <td>{{ line.result }}</td>
            <td>{{ line.points }}</td>
            <td>{{ line.imp }}</td>
          </tr>
          <tr v-if="average !== undefined">
            <td colspan="2"></td>
            <td colspan="4">Průměr: {{ average }}</td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.jack {
  color: #777;
}
</style>
