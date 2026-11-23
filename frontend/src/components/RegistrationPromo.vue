<template>
  <aside v-if="shown" class="promo" :class="size" :aria-label="$t('promo.kicker')">
    <div class="message">
      <h2 class="title">{{ localName(language, shown.title, shown.titleEn) }}</h2>
      <p v-if="text" class="lead">{{ text }}</p>
      <p v-if="facts.length" class="facts">{{ facts.join(' · ') }}</p>
    </div>
    <div class="action">
      <component :is="preview ? 'span' : 'a'" :href="preview ? undefined : shown.url ?? undefined"
                 :target="preview ? undefined : '_blank'" class="cta">
        {{ (language === 'en' ? shown.linkLabelEn : shown.linkLabel) || $t('promo.cta') }}
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
