<template>
  <div class="club-bar" :class="{ hidden }">
    <a href="https://www.bkpraha.cz/" class="club-home" tabindex="-1" aria-hidden="true">
      <img src="@/assets/logo.png" alt="" class="club-logo">
      <span class="club-name">Bridžový klub Praha</span>
    </a>
    <a href="https://www.bkpraha.cz/" class="club-back">
      <svg viewBox="0 0 16 16" aria-hidden="true">
        <path d="M10 3 5 8l5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      Zpět na bkpraha.cz
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
  justify-content: space-between;
  gap: 12px;
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
  min-width: 0;
  color: #393939;
  text-decoration: none;
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
  white-space: nowrap;
}

/* Spelled out for everyone: where the button goes and that it goes back. */
.club-back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
  padding: 9px 16px 9px 12px;
  border-radius: 999px;
  background: #1a881a;
  color: white;
  font-family: 'Lexend', Verdana, sans-serif;
  font-size: 1rem;
  font-weight: 600;
  text-decoration: none;
}

.club-back:hover {
  background: #0e5a1b;
  color: white;
}

.club-back:focus-visible {
  outline: 3px solid #0e5a1b;
  outline-offset: 3px;
}

.club-back svg {
  width: 18px;
  height: 18px;
}

@media (max-width: 720px) {
  .club-bar {
    height: 58px;
    padding: 0 12px;
  }

  .club-logo {
    height: 40px;
  }

  /* On a phone the logo names the club and the button gets the room. */
  .club-name {
    display: none;
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
