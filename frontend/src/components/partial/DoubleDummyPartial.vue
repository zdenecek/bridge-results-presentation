<template>
  <div class="flex flex-column">

    <table>
      <tr>
        <th></th>
        <th>
          <SuitPartial suit="N" />
        </th>
        <th>
          <SuitPartial suit="S" />
        </th>
        <th>
          <SuitPartial suit="H" />
        </th>
        <th>
          <SuitPartial suit="D" />
        </th>
        <th>
          <SuitPartial suit="C" />
        </th>
      </tr>
      <tr v-for="[pos, i] in [['N', 0], ['S', 2], ['E', 1], ['W', 3]]" :key="i">
        <td>{{ pos }}</td>
        <td v-for="s in 5" :key="s">
          {{ dd[ 5 * (i as number) + s - 1] }}
        </td>
      </tr>
    </table>
    <div>
      Minimax:
      <ContractPartial :contract="minimaxContract" :declarer="minimaxDecl" /> <span>{{ minimaxResult.charAt(0) === "-" ?
        minimaxResult : "+" + minimaxResult }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, defineProps } from 'vue';
import SuitPartial from './SuitPartial.vue';
import ContractPartial from './ContractPartial.vue';


const props = defineProps({
  dd: {
    type: Array,
    required: true,
  },
  minimax: {
    type: String,
    required: true,
  }

})

const minimaxContract = computed(() => props.minimax.charAt(2) === "D" ?
  props.minimax.substring(0, 3) :
  props.minimax.substring(0, 2));
const minimaxDecl = computed(() => {
  const char2 = props.minimax.charAt(2);
  if (char2 === "D") return props.minimax.charAt(3);
  return char2;
});
const minimaxResult = computed(() => props.minimax.substring(props.minimax.charAt(2) === "D" ? 4 : 3));



</script>

<style scoped>
table {
  border-collapse: collapse;
}

th {
  width: 1em;
  padding: 4px;

  border-top: 1px solid lightgray;
  border-bottom: 1px solid lightgray;
}

.flex {
  gap: 10px;
}


td:first-child {
  padding-right: 10px;
}

td {
  width: 2em;
  padding: 6px;
  text-align: center;
}

tr:nth-child(2n) {
  background-color: #f2f2f2;
}</style>