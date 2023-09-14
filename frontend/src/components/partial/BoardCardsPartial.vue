<template>
  <div class="hand" v-if="suits">
    <SuitPartial suit="S" /><span>{{ suits[0] }}</span>
    <SuitPartial suit="H" /><span>{{ suits[1] }}</span>
    <SuitPartial suit="D" /><span>{{ suits[2] }}</span>
    <SuitPartial suit="C" /><span>{{ suits[3] }}</span>
  </div>
</template>

<script setup lang="ts">
import { computed, defineProps } from 'vue';
import SuitPartial from './SuitPartial.vue';

const props = defineProps({
  cards: {
    type: String,
    required: true,
  },

})

const suits = computed(() => props.cards?.split('.'));
</script>

<style scoped>
.hand {
  display: grid;
  grid-template-columns: 15px auto;
  font-size: 1.05em;
}


span {
  text-align: left;
}
</style>