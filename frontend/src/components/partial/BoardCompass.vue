<template>
  <svg class="compass" viewBox="0 0 168 168" role="img"
       :aria-label="$t('tournament.compass', { dealer, vul })">
    <g v-for="seat in SEATS" :key="seat.name">
      <path v-if="seat.name === dealer" :d="DEALER" :transform="`rotate(${seat.angle} 84 84)`" :fill="color(seat.name)" />
      <circle v-else :cx="seat.x" :cy="seat.y" r="28" :fill="color(seat.name)" />
      <text :x="seat.x" :y="seat.y" text-anchor="middle" dominant-baseline="central">{{ seat.name }}</text>
    </g>
  </svg>
</template>

<script setup lang="ts">
const props = defineProps<{ dealer: string; vul: string }>();

/** Like the Tournament Calculator: four touching discs, the dealer's one drawn to a point outwards. */
const SEATS = [
  { name: 'N', x: 84, y: 42, angle: 0 },
  { name: 'E', x: 126, y: 84, angle: 90 },
  { name: 'S', x: 84, y: 126, angle: 180 },
  { name: 'W', x: 42, y: 84, angle: 270 },
];

/** North's disc with a corner at the top; the other seats get it rotated about the middle. */
const DEALER = 'M84 2.4 L103.8 22.2 A28 28 0 1 1 64.2 22.2 Z';

function color(seat: string): string {
  const ns = seat === 'N' || seat === 'S';
  const vulnerable = props.vul === 'All' || props.vul === (ns ? 'NS' : 'EW');
  return vulnerable ? '#d93636' : '#1a881a';
}
</script>

<style scoped>
.compass {
  display: block;
  width: 100%;
  max-width: 92px;
  margin: auto;
}

text {
  fill: white;
  font-family: 'Lexend', Verdana, sans-serif;
  font-size: 34px;
  font-weight: 700;
}
</style>
