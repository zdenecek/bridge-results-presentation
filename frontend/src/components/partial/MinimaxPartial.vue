<template>
  <div>
    Minimax:
    <ContractPartial :contract="minimaxContract" :declarer="minimaxDecl" /> <span>{{ minimaxResult.charAt(0) === "-" ?
      minimaxResult : "+" + minimaxResult }}</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import ContractPartial from './ContractPartial.vue';

const props = defineProps({
  minimax: {
    type: String,
    required: true,
  },
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
