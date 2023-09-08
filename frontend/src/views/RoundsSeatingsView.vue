<template>
  <div>
    <h2>Nasazení</h2>
    <nav>
      <router-link :to="{ name: 'tournament-results' }">Výsledky</router-link>
    </nav>
    <template v-if="tournament">

      <nav>
        <router-link :to="{ params: { round: rnd } }"
                     :class="{ 'router-link-exact-active': round === rnd, disabled: false }"
                     v-for="rnd in tournament.totalRounds" :key="rnd">
          {{ rnd }}</router-link>
        <router-link :to="{ params: { round: next } }" v-if="!tournament.isFinished"
                     :class="{ 'router-link-exact-active': round === next }">Aktuální</router-link>
      </nav>
      <RoundSeatings :round="round" :tournament="tournament" />
    </template>
  </div>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router';

import { Ref, computed, inject } from 'vue';
import { Tournament } from '@/model/Tournament';
import RoundSeatings from '@/components/RoundSeatings.vue';

const route = useRoute();

const tournament = inject('tournament') as Ref<Tournament | undefined>;
const next = computed(() => Math.min((tournament.value?.standing ?? 0) + 1, tournament.value?.totalRounds ?? 1));


let round = computed(() => {
  const val = Number.parseInt(route.params['round'] as string)
  if (isNaN(val) && next.value) return next.value;
  return val;
});

</script>

<style scoped></style>
