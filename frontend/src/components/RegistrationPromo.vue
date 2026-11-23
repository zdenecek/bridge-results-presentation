<template>
  <aside v-if="shown && t" class="promo" :class="size" :aria-label="$t('promo.kicker')">
    <div class="message">
      <span class="kicker"><span class="dot" aria-hidden="true"></span>{{ $t('promo.kicker') }}</span>
      <h2 class="title">{{ t.title }}</h2>
      <p v-if="t.text" class="lead">{{ t.text }}</p>
      <p v-if="t.facts.length" class="facts">{{ t.facts.join(' · ') }}</p>
    </div>
    <div class="action">
      <component :is="preview ? 'span' : 'a'" :href="preview ? undefined : shown.url ?? undefined"
                 :target="preview ? undefined : '_blank'" class="cta">
        {{ t.linkLabel || $t('promo.cta') }}
      </component>
      <span v-if="t.deadline" class="deadline">{{ $t('promo.deadline', { when: t.deadline }) }}</span>
    </div>
  </aside>
</template>

<script setup lang="ts">
import PromoApi, { Promo } from '@/api/PromoApi';
import { computed, ref } from 'vue';
import { language } from '@/i18n';
import { promoTexts } from '@/utils/promo';

/**
 * Registration for the next cycle, set up in the admin. Large wherever it is placed; given a tournament,
 * it shows only on that tournament's pages and a little larger. With a preview, as in the admin, it shows that one as it is.
 */
const props = defineProps<{ tournament?: string; preview?: Promo; hero?: boolean }>();

const promo = ref<Promo | null>(null);
if (!props.preview) PromoApi.current().then((p) => promo.value = p);

const size = computed(() => props.hero || props.tournament ? 'hero' : 'large');
const shown = computed(() => {
  if (props.preview) return props.preview.title ? props.preview : null;
  const p = promo.value;
  if (!p || (props.tournament && p.tournament !== props.tournament)) return null;
  return p;
});
const t = computed(() => shown.value && promoTexts(shown.value, language.value));
</script>

<style scoped>
.promo {
  display: flex;
  align-items: center;
  gap: 16px 28px;
  margin: 0 0 24px;
  padding: 18px 22px;
  border: 1px solid #d9e0d9;
  border-left: 6px solid #1a881a;
  border-radius: 8px;
  background: white;
  color: #222;
  font-family: 'Lexend', Verdana, sans-serif;
  text-align: left;
}

.message {
  flex: 1;
  min-width: 0;
}

.kicker {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  margin-bottom: 2px;
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

@media (prefers-reduced-motion: reduce) {
  .dot { animation: none; }
}

.promo .title {
  margin: 0 0 4px;
  color: #1a881a;
  font-size: 1.375rem;
  line-height: 1.2;
  font-weight: 700;
}

.lead,
.facts {
  margin: 0;
  font-size: 1rem;
  line-height: 1.45;
}

.facts {
  margin-top: 2px;
  color: #5f6661;
  font-size: 0.9375rem;
}

.action {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.promo .cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 44px;
  padding: 0 22px;
  border-radius: 6px;
  background: #1a881a;
  color: white;
  font-size: 1rem;
  font-weight: 600;
  text-decoration: none;
  white-space: nowrap;
}

.promo .cta:hover {
  background: #0e5a1b;
}

.promo .cta:focus-visible {
  outline: 3px solid #f2c14e;
  outline-offset: 2px;
}

.deadline {
  color: #5f6661;
  font-size: 0.8125rem;
}

.hero {
  padding: 22px 26px;
}

.promo.hero .title {
  font-size: 1.75rem;
}

.hero .lead {
  font-size: 1.0625rem;
}

.hero .cta {
  min-height: 48px;
  padding: 0 26px;
  font-size: 1.0625rem;
}

@media (max-width: 640px) {
  .promo {
    flex-direction: column;
    align-items: stretch;
    margin: 0 8px 20px;
    padding: 16px;
  }

  .promo.hero .title {
    font-size: 1.5rem;
  }

  .action {
    align-items: stretch;
    text-align: center;
  }
}

@media print {
  .promo { display: none; }
}
</style>
