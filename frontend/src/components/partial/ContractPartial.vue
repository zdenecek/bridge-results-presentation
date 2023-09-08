<template>
  <span v-if="pass">Pass</span>
  <span v-else>
    <span>{{ level }}</span>
    <Suit :suit="suit" /><span v-if="double">{{ double }}</span> <span>{{ props.declarer }}</span>
  </span>
</template>

<script setup lang="ts">

import { computed, defineProps } from 'vue';
import Suit from './SuitPartial.vue';

const props = defineProps({
  contract: {
    type: String,
    required: true,
  },
  declarer: {
    type: String,
    required: true,
  },
})

const contrct = props.contract.trim().replaceAll(" ", "");
const level = contrct[0];
const suit = contrct[1];
const double = computed(() => {
  const val = contrct.substring(2);
  if (val === "D" || val === "d" || val === "X" || val === "x") return "x";
  if (val === "R" || val === "r" || val === "XX" || val === "xx") return "xx";
  return undefined;
})
const pass = level.toLowerCase() === "p";

</script>
