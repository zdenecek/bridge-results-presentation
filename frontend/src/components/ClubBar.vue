<template>
  <div class="club-bar" :class="{ hidden }">
    <a href="https://www.bkpraha.cz/" class="club-home">
      <img src="@/assets/logo.png" alt="" class="club-logo">
      <span class="club-name">Bridžový klub Praha</span>
    </a>
  </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

/** Like the header of bkpraha.cz, but out of the way while reading down and back as soon as you scroll up. */
const hidden = ref(false);
let lastY = 0;
let ticking = false;

function onScroll() {
  if (ticking) return;
  ticking = true;
  requestAnimationFrame(() => {
    const y = window.scrollY;
    if (Math.abs(y - lastY) > 4) {
      hidden.value = y > lastY && y > 80;
      lastY = y;
    }
    ticking = false;
  });
}

onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }));
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));
</script>

<style scoped>
.club-bar {
  position: sticky;
  top: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  height: 64px;
  padding: 0 24px;
  background: #f9f7fa;
  border-bottom: 1px solid #e6e3e8;
  transition: transform 0.25s ease;
}

.club-bar.hidden {
  transform: translateY(-100%);
}

.club-home {
  display: inline-flex;
  align-items: center;
  gap: 14px;
  color: #393939;
  text-decoration: none;
}

.club-home:hover .club-name {
  color: #1a881a;
}

.club-home:focus-visible {
  outline: 2px solid #1a881a;
  outline-offset: 4px;
  border-radius: 4px;
}

.club-logo {
  height: 46px;
  width: auto;
}

.club-name {
  font-family: 'Roboto Flex', Roboto, Arial, sans-serif;
  font-size: 1.625rem;
  font-weight: 400;
  letter-spacing: -0.03em;
  text-transform: uppercase;
  transition: color 0.15s ease;
}

@media (max-width: 640px) {
  .club-bar {
    height: 56px;
    padding: 0 14px;
  }

  .club-logo {
    height: 38px;
  }

  .club-name {
    font-size: 1.25rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  .club-bar {
    transition: none;
  }
}

@media print {
  .club-bar {
    display: none;
  }
}
</style>
