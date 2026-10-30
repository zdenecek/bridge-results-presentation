<template>
  <div ref="root" class="board-stepper">
    <nav class="pills board-pills" aria-label="Rozdání">
      <router-link v-for="b in boards" :key="b" :class="{ active: current === b }" :to="to(b)">{{ b }}</router-link>
      <router-link :class="{ active: current === 'all' }" :to="to('all')">Všechna</router-link>
    </nav>

    <!-- On a phone it floats at the bottom, under the thumb, and the list of boards becomes a dropdown. -->
    <div class="step" :class="{ all: current === 'all' }">
      <router-link v-if="prev !== undefined" class="step-btn" :to="to(prev)">‹ Předchozí</router-link>
      <span v-else class="step-btn disabled" aria-hidden="true">‹ Předchozí</span>

      <span class="step-label">{{ current }} / {{ boards[boards.length - 1] }}</span>
      <select class="step-select" :value="current" aria-label="Rozdání" @change="pick">
        <option v-for="b in boards" :key="b" :value="b">Rozdání {{ b }}</option>
        <option value="all">Všechna rozdání</option>
      </select>

      <router-link v-if="next !== undefined" class="step-btn" :to="to(next)">Další ›</router-link>
      <span v-else class="step-btn disabled" aria-hidden="true">Další ›</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue';
import { RouteLocationRaw, useRouter } from 'vue-router';

const props = defineProps<{
  boards: number[];
  current: number | 'all' | undefined;
  to: (board: number | 'all') => RouteLocationRaw;
}>();

const router = useRouter();
const root = ref<HTMLElement>();

const index = computed(() => typeof props.current === 'number' ? props.boards.indexOf(props.current) : -1);
const prev = computed(() => index.value > 0 ? props.boards[index.value - 1] : undefined);
const next = computed(() => index.value >= 0 && index.value < props.boards.length - 1 ? props.boards[index.value + 1] : undefined);

function pick(event: Event) {
  const value = (event.target as HTMLSelectElement).value;
  router.push(props.to(value === 'all' ? 'all' : Number(value)));
}

/** Stepping from the bottom of one board lands on the top of the next, not on its results. */
onMounted(() => nextTick(() => {
  if (root.value && root.value.getBoundingClientRect().top < 0) root.value.scrollIntoView();
}));
</script>

<style scoped>
.board-stepper {
  scroll-margin-top: 70px;
}

.step {
  display: flex;
  align-items: center;
  gap: 14px;
  margin: 0 0 16px;
}

.step.all {
  display: none;
}

.step-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 7.5em;
  min-height: 44px;
  padding: 0 16px;
  border: 1px solid var(--felt);
  border-radius: 999px;
  background: white;
  color: var(--felt);
  font-weight: 600;
  text-decoration: none;
  white-space: nowrap;
  user-select: none;
}

a.step-btn:hover {
  background: var(--felt);
  color: white;
  text-decoration: none;
}

.step-btn.disabled {
  border-color: #d5dad5;
  color: #b3bab4;
}

.step-label {
  min-width: 4.5em;
  color: var(--ink-muted);
  font-variant-numeric: tabular-nums;
  text-align: center;
}

.step-select {
  display: none;
}

@media (max-width: 640px) {
  .board-pills {
    display: none;
  }

  .step,
  .step.all {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    position: fixed;
    z-index: 50;
    left: 10px;
    right: 10px;
    bottom: max(12px, env(safe-area-inset-bottom));
    max-width: 480px;
    margin: 0 auto;
    padding: 6px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.97);
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.18);
  }

  .step-btn {
    flex: 1;
    min-width: 0;
    padding: 0 10px;
  }

  .step-label {
    display: none;
  }

  /* At least 16px, or iOS zooms the page in when it opens. */
  .step-select {
    display: block;
    flex: 1.2;
    min-width: 0;
    min-height: 44px;
    padding: 0 10px;
    border: 1px solid #d5dad5;
    border-radius: 999px;
    background: white;
    color: var(--ink);
    font: inherit;
    font-size: 1rem;
    font-weight: 600;
    text-align: center;
  }
}
</style>

<style>
@media (max-width: 640px) {
  .layout-wrapper:has(.board-stepper) .footer-wrapper {
    padding-bottom: 90px;
  }
}
</style>
