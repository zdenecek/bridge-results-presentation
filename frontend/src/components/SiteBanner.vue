<template>
  <aside v-if="shown" class="site-banner" aria-label="Pozvánka">
    <div class="inner">
      <span class="badge" aria-hidden="true">
        <svg viewBox="0 0 24 24">
          <path d="M7 4h10v5a5 5 0 0 1-10 0V4Z" />
          <path d="M7 6H4.5a.5.5 0 0 0-.5.5V8a3 3 0 0 0 3 3M17 6h2.5a.5.5 0 0 1 .5.5V8a3 3 0 0 1-3 3M12 14v3M8.5 20h7M9.5 20l.5-3h4l.5 3" />
        </svg>
      </span>
      <div class="message">
        <span class="kicker">Pozvánka</span>
        <strong class="title">{{ shown.title }}</strong>
        <span v-if="shown.text" class="text">{{ shown.text }}</span>
      </div>
      <component :is="preview ? 'span' : 'a'" v-if="shown.url" :href="preview ? undefined : shown.url"
                 :target="preview ? undefined : '_blank'" class="cta">
        {{ shown.linkLabel || 'Více informací' }}
        <svg viewBox="0 0 16 16" aria-hidden="true">
          <path d="M6 3l5 5-5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </component>
      <button v-if="!preview" type="button" class="close" aria-label="Skrýt pozvánku" @click="dismiss">
        <svg viewBox="0 0 16 16" aria-hidden="true">
          <path d="M4 4l8 8M12 4l-8 8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
      </button>
    </div>
  </aside>
</template>

<script setup lang="ts">
import BannerApi, { Banner } from '@/api/BannerApi';
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';

/** With a preview, as in the admin, it shows that banner as it is and never loads or hides. */
const props = defineProps<{ preview?: Banner }>();

const STORAGE_KEY = 'bannerDismissed';

/** Loaded once per visit, not on every page. */
let loading: Promise<Banner | null> | undefined;

const route = useRoute();
const banner = ref<Banner | null>(null);
const dismissed = ref(read());

if (!props.preview) (loading ??= BannerApi.get().catch(() => null)).then((b) => banner.value = b);

const key = computed(() => banner.value ? `${banner.value.title}|${banner.value.until ?? ''}` : '');
const shown = computed(() => {
  if (props.preview) return props.preview.title ? props.preview : null;
  const name = String(route.name ?? '');
  if (name.startsWith('admin') || name.startsWith('print') || key.value === dismissed.value) return null;
  return banner.value;
});

function read(): string {
  try {
    return localStorage.getItem(STORAGE_KEY) ?? '';
  } catch {
    return '';
  }
}

/** Hidden for this invitation only; a new one shows up again. */
function dismiss() {
  dismissed.value = key.value;
  try {
    localStorage.setItem(STORAGE_KEY, key.value);
  } catch {
    // Blocked storage: hidden until the page is reloaded.
  }
}
</script>

<style scoped>
/* Deep felt green with a gold cup: an invitation, set apart from the results below it. */
.site-banner {
  position: relative;
  overflow: hidden;
  background: linear-gradient(105deg, #0b4a16 0%, #14721a 55%, #1a881a 100%);
  border-bottom: 1px solid rgba(0, 0, 0, 0.25);
  color: white;
  font-family: 'Lexend', Verdana, sans-serif;
  text-align: left;
}

.site-banner::after {
  content: '♠♥♦♣';
  position: absolute;
  right: -0.1em;
  top: 50%;
  transform: translateY(-50%);
  font-size: 5.5rem;
  line-height: 1;
  letter-spacing: -0.05em;
  color: white;
  opacity: 0.06;
  pointer-events: none;
}

.inner {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 12px 18px;
  max-width: 1140px;
  margin: 0 auto;
  padding: 16px 19px;
}

.badge {
  flex-shrink: 0;
  display: grid;
  place-items: center;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.12);
  box-shadow: inset 0 0 0 1px rgba(242, 193, 78, 0.45);
}

.badge svg {
  width: 26px;
  height: 26px;
  fill: none;
  stroke: #f2c14e;
  stroke-width: 1.6;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.message {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}

.kicker {
  color: #f2c14e;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.title {
  font-size: 1.25rem;
  line-height: 1.25;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.text {
  color: rgba(255, 255, 255, 0.86);
  font-size: 0.9375rem;
  line-height: 1.4;
}

.cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  flex-shrink: 0;
  min-height: 44px;
  padding: 0 16px 0 20px;
  border-radius: 999px;
  background: #f2c14e;
  color: #1d2a1d;
  font-weight: 600;
  text-decoration: none;
  white-space: nowrap;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  transition: background 0.15s ease, transform 0.15s ease;
}

a.cta:hover {
  background: #f7d27a;
  transform: translateY(-1px);
}

.cta svg {
  width: 16px;
  height: 16px;
}

.cta:focus-visible,
.close:focus-visible {
  outline: 3px solid white;
  outline-offset: 2px;
}

.close {
  flex-shrink: 0;
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: transparent;
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
}

.close svg {
  width: 16px;
  height: 16px;
}

.close:hover {
  background: rgba(255, 255, 255, 0.12);
  color: white;
}

@media (max-width: 640px) {
  .site-banner::after {
    top: 18px;
    transform: none;
    font-size: 4rem;
  }

  .inner {
    flex-wrap: wrap;
    align-items: flex-start;
    padding: 14px 12px 14px 16px;
  }

  .badge {
    width: 40px;
    height: 40px;
  }

  .badge svg {
    width: 22px;
    height: 22px;
  }

  .message {
    flex-basis: calc(100% - 40px - 36px - 36px);
  }

  .title {
    font-size: 1.125rem;
  }

  .close {
    margin: -4px -4px 0 0;
  }

  .cta {
    order: 3;
    width: 100%;
  }
}

@media (prefers-reduced-motion: reduce) {
  .cta {
    transition: none;
  }
}

@media print {
  .site-banner {
    display: none;
  }
}
</style>
