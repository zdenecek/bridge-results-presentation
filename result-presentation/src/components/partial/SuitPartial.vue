<template>
  <span>
    <span class="suit" :class="['suit-' + suit.toLowerCase()]">{{ suitStr }}</span>
  </span>
</template>

<script setup lang="ts">

import { defineProps } from 'vue';

const props = defineProps({
  suit: {
    type: String,
    required: true,
  },
})


const suitStr = (() => {
  switch (props.suit) {
    case 'S': return '♠';
    case 'H': return '♥';
    case 'D': return '♦';
    case 'C': return '♣';
    case 'N': return 'NT';
    default: return props.suit;
  }
})();

</script>

<style scoped>
.suit-c {
  color: #32AD01
}

.suit-d {
  color: #FF9B00
}

.suit-h {
  color: #FF0000
}

.suit-s {
  color: #33339B
}

.suit-n {
  font-weight: bold;
  font-size: 0.9em;
}
</style>