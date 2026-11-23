<template>
  <aside v-if="shown" class="promo" :class="size" :aria-label="$t('promo.kicker')">
    <div class="message">
      <span class="kicker"><span class="dot" aria-hidden="true"></span>{{ $t('promo.kicker') }}</span>
      <h2 class="title">{{ localName(language, shown.title, shown.titleEn) }}</h2>
      <p v-if="text" class="lead">{{ text }}</p>
      <ul v-if="facts.length" class="facts">
        <li v-for="fact in facts" :key="fact">{{ fact }}</li>
      </ul>
    </div>
    <div class="action">
      <component :is="preview ? 'span' : 'a'" :href="preview ? undefined : shown.url ?? undefined"
                 :target="preview ? undefined : '_blank'" class="cta">
        {{ (language === 'en' ? shown.linkLabelEn : shown.linkLabel) || $t('promo.cta') }}
        <svg viewBox="0 0 16 16" aria-hidden="true">
          <path d="M6 3l5 5-5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </component>
      <span v-if="deadline" class="deadline">{{ $t('promo.deadline', { when: deadline }) }}</span>
    </div>
  </aside>
</template>

<script setup lang="ts">
import PromoApi, { Promo } from '@/api/PromoApi';
import { computed, ref } from 'vue';
import { language } from '@/i18n';
import { localName } from '@/i18n/names';

/**
 * Registration for the next cycle, set up in the admin. Large wherever it is placed; given a tournament,
 * it shows only on that tournament's pages and larger still. With a preview, as in the admin, it shows that one as it is.
 */
const props = defineProps<{ tournament?: string; preview?: Promo; hero?: boolean }>();

/** Loaded once per visit, not on every page. */
let loading: Promise<Promo | null> | undefined;

const promo = ref<Promo | null>(null);
if (!props.preview) (loading ??= PromoApi.get().catch(() => null)).then((p) => promo.value = p);

const size = computed(() => props.hero || props.tournament ? 'hero' : 'large');
const shown = computed(() => {
  if (props.preview) return props.preview.title ? props.preview : null;
  const p = promo.value;
  if (!p || (props.tournament && p.tournament !== props.tournament)) return null;
  return p;
});
const text = computed(() => shown.value && ((language.value === 'en' && shown.value.textEn) || shown.value.text));
const facts = computed(() => {
  const value = shown.value && ((language.value === 'en' && shown.value.factsEn) || shown.value.facts);
  return (value ?? '').split(';').map((f) => f.trim()).filter(Boolean);
});
/** The closing time as written, in Prague time, so visitors abroad see the same clock as the club. */
const deadline = computed(() => {
  const closes = shown.value?.closes;
  if (!closes || !/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(closes)) return '';
  const date = new Date(closes + ':00Z');
  return new Intl.DateTimeFormat(language.value === 'en' ? 'en-GB' : 'cs-CZ', {
    weekday: 'short', day: 'numeric', month: language.value === 'en' ? 'short' : 'numeric',
    hour: '2-digit', minute: '2-digit', timeZone: 'UTC',
  }).format(date);
});
</script>

<style scoped>
.promo {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  gap: 20px 32px;
  margin: 0 0 28px;
  padding: 24px 28px;
  border-radius: 14px;
  background: linear-gradient(115deg, #0b4a16 0%, #14721a 55%, #1a881a 100%);
  box-shadow: 0 6px 24px rgba(11, 74, 22, 0.25), inset 0 0 0 1px rgba(242, 193, 78, 0.35);
  color: white;
  font-family: 'Lexend', Verdana, sans-serif;
  text-align: left;
}

.promo::after {
  content: '♠♥♦♣';
  position: absolute;
  right: -0.08em;
  bottom: -0.18em;
  font-size: 8rem;
  line-height: 1;
  letter-spacing: -0.05em;
  color: white;
  opacity: 0.06;
  pointer-events: none;
}

.message,
.action {
  position: relative;
  z-index: 1;
}

.message {
  flex: 1;
  min-width: 0;
}

.kicker {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #f2c14e;
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #f2c14e;
  box-shadow: 0 0 0 0 rgba(242, 193, 78, 0.6);
  animation: pulse 2s infinite;
}

.promo .title {
  margin: 4px 0 6px;
  color: white;
  font-size: 1.875rem;
  line-height: 1.15;
  font-weight: 700;
  letter-spacing: -0.015em;
}

.lead {
  margin: 0;
  color: rgba(255, 255, 255, 0.9);
  font-size: 1.0625rem;
  line-height: 1.45;
}

.facts {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 14px 0 0;
  padding: 0;
  list-style: none;
}

.facts li {
  padding: 3px 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  color: rgba(255, 255, 255, 0.92);
  font-size: 0.875rem;
}

.action {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.promo .cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 52px;
  padding: 0 22px 0 28px;
  border-radius: 999px;
  background: #f2c14e;
  color: #1d2a1d;
  font-size: 1.125rem;
  font-weight: 700;
  text-decoration: none;
  white-space: nowrap;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
  transition: background 0.15s ease, transform 0.15s ease;
}

.promo .cta:hover {
  background: #f7d27a;
  transform: translateY(-1px);
}

.promo .cta:focus-visible {
  outline: 3px solid white;
  outline-offset: 3px;
}

.cta svg {
  width: 18px;
  height: 18px;
}

.deadline {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.8125rem;
}

.hero {
  flex-direction: column;
  align-items: flex-start;
  gap: 28px;
  margin-bottom: 36px;
  padding: 44px 48px;
  border-radius: 18px;
}

.hero::after {
  font-size: 15rem;
}

.hero .kicker {
  font-size: 0.9375rem;
}

.promo.hero .title {
  margin: 8px 0 10px;
  font-size: 3rem;
  line-height: 1.05;
}

.hero .lead {
  max-width: 34em;
  font-size: 1.3125rem;
}

.hero .facts {
  margin-top: 20px;
}

.hero .facts li {
  padding: 5px 16px;
  font-size: 1rem;
}

.hero .action {
  flex-direction: row;
  gap: 16px;
}

.hero .cta {
  min-height: 60px;
  padding: 0 28px 0 36px;
  font-size: 1.3125rem;
}

.hero .deadline {
  font-size: 1rem;
}

@media (max-width: 640px) {
  .promo {
    flex-direction: column;
    align-items: stretch;
    margin: 0 8px 24px;
    padding: 20px;
  }

  .promo .title {
    font-size: 1.5rem;
  }

  .action {
    align-items: stretch;
  }

  .hero {
    padding: 28px 20px;
  }

  .promo.hero .title {
    font-size: 2.125rem;
  }

  .hero .lead {
    font-size: 1.125rem;
  }

  .hero .action {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }

  .deadline {
    text-align: center;
  }
}

@keyframes pulse {
  70% { box-shadow: 0 0 0 8px rgba(242, 193, 78, 0); }
  100% { box-shadow: 0 0 0 0 rgba(242, 193, 78, 0); }
}

@media (prefers-reduced-motion: reduce) {
  .dot { animation: none; }
  .promo .cta { transition: none; }
}

@media print {
  .promo { display: none; }
}
</style>
