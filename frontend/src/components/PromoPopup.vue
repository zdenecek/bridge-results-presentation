<template>
  <dialog ref="dialog" class="promo-popup" :aria-label="$t('promo.kicker')" @close="open = false" @click="closeOnBackdrop">
    <template v-if="promo && t">
      <button type="button" class="close" :aria-label="$t('promo.close')" @click="close">
        <svg viewBox="0 0 16 16" aria-hidden="true">
          <path d="M4 4l8 8M12 4l-8 8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
      </button>
      <span class="kicker"><span class="dot" aria-hidden="true"></span>{{ $t('promo.kicker') }}</span>
      <h2>{{ t.title }}</h2>
      <p v-if="t.text" class="lead">{{ t.text }}</p>
      <p v-if="t.facts.length" class="facts">{{ t.facts.join(' · ') }}</p>
      <div class="actions">
        <a :href="promo.url ?? undefined" target="_blank" class="cta" @click="close">{{ t.linkLabel || $t('promo.cta') }}</a>
        <button type="button" class="later" @click="close">{{ $t('promo.later') }}</button>
      </div>
      <p v-if="t.deadline" class="deadline">{{ $t('promo.deadline', { when: t.deadline }) }}</p>
    </template>
  </dialog>
</template>

<script setup lang="ts">
import PromoApi, { Promo } from '@/api/PromoApi';
import { language } from '@/i18n';
import { promoTexts } from '@/utils/promo';
import { computed, onBeforeUnmount, ref } from 'vue';

/** Shows the promo once, as a popup, on the pages of the tournament it is set up for. */
const props = defineProps<{ tournament: string }>();

const STORAGE_KEY = 'promoPopupSeen';

/** The project's DOM typings predate `showModal`, which every current browser has. */
type Dialog = HTMLElement & { showModal(): void; close(): void };
const dialog = ref<Dialog>();
const promo = ref<Promo | null>(null);
const open = ref(false);
const t = computed(() => promo.value && promoTexts(promo.value, language.value));
let timer: number | undefined;

const keyOf = (p: Promo) => `${p.title}|${p.closes ?? ''}`;

function seen(p: Promo): boolean {
  try {
    return localStorage.getItem(STORAGE_KEY) === keyOf(p);
  } catch {
    return true;
  }
}

PromoApi.current().then((p) => {
  if (!p || p.tournament !== props.tournament || seen(p)) return;
  promo.value = p;
  timer = window.setTimeout(() => {
    dialog.value?.showModal();
    open.value = true;
    try {
      localStorage.setItem(STORAGE_KEY, keyOf(p));
    } catch {
      // Without storage it would show on every page; the check above already skips it then.
    }
  }, 800);
});

onBeforeUnmount(() => window.clearTimeout(timer));

function close() {
  dialog.value?.close();
}

function closeOnBackdrop(event: MouseEvent) {
  if (event.target === dialog.value) close();
}
</script>

<style scoped>
.promo-popup {
  width: min(460px, calc(100vw - 32px));
  padding: 28px 28px 22px;
  border: none;
  border-top: 6px solid #1a881a;
  border-radius: 10px;
  background: white;
  color: #222;
  font-family: 'Lexend', Verdana, sans-serif;
  text-align: left;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
}

.promo-popup::backdrop {
  background: rgba(20, 25, 20, 0.55);
}

.close {
  position: absolute;
  top: 10px;
  right: 10px;
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: transparent;
  color: #5f6661;
  cursor: pointer;
}

.close:hover {
  background: #eef1ee;
}

.close svg {
  width: 16px;
  height: 16px;
}

.kicker {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  color: #1a881a;
  font-size: 0.8125rem;
  font-weight: 600;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #1a881a;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { box-shadow: 0 0 0 0 rgba(26, 136, 26, 0.55); }
  70% { box-shadow: 0 0 0 8px rgba(26, 136, 26, 0); }
  100% { box-shadow: 0 0 0 0 rgba(26, 136, 26, 0); }
}

h2 {
  margin: 6px 36px 8px 0;
  color: #1a881a;
  font-size: 1.625rem;
  line-height: 1.2;
}

.lead {
  margin: 0;
  font-size: 1.0625rem;
  line-height: 1.45;
}

.facts,
.deadline {
  margin: 6px 0 0;
  color: #5f6661;
  font-size: 0.9375rem;
}

.deadline {
  font-size: 0.8125rem;
}

.actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px 16px;
  margin-top: 22px;
}

.cta {
  display: inline-flex;
  align-items: center;
  min-height: 46px;
  padding: 0 26px;
  border-radius: 6px;
  background: #1a881a;
  color: white;
  font-weight: 600;
  text-decoration: none;
}

.cta:hover {
  background: #0e5a1b;
}

.later {
  padding: 8px 4px;
  border: none;
  background: none;
  color: #5f6661;
  font: inherit;
  cursor: pointer;
}

.later:hover {
  color: #222;
  text-decoration: underline;
}

.cta:focus-visible,
.later:focus-visible,
.close:focus-visible {
  outline: 3px solid #f2c14e;
  outline-offset: 2px;
}

@media (max-width: 640px) {
  .promo-popup {
    padding: 24px 20px 18px;
  }

  .cta {
    flex: 1;
    justify-content: center;
  }
}

@media (prefers-reduced-motion: reduce) {
  .dot { animation: none; }
}
</style>
