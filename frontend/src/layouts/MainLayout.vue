<template>
  <div class="layout-wrapper">
    <club-bar />
    <site-banner />
    <div class="layout-header">
      <div class="image">
        <slot name="hero">
          <h1>
            <slot name="header"></slot>
          </h1>
        </slot>
      </div>
    </div>
    <div class="content">
      <div class="container">
        <slot></slot>
      </div>
    </div>
    <footer>
      <div class="footer-wrapper">
        <div class="footer-inner">
          <slot name="footer"></slot>
          <p class="webmaster">
            <span>
              {{ $t('footer.bug') }}
              <a href="mailto:zdnek.tomis@gmail.com">zdnek.tomis@gmail.com</a>
            </span>
            <span v-if="!czechOnly" class="lang">
              <template v-for="(lang, i) in LANGS" :key="lang">
                <span v-if="i" aria-hidden="true"> · </span>
                <strong v-if="language === lang" :lang="lang">{{ $t(`lang.${lang}Name`) }}</strong>
                <a v-else href="#" :lang="lang" @click.prevent="setLanguage(lang)">{{ $t(`lang.${lang}Name`) }}</a>
              </template>
            </span>
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import ClubBar from '@/components/ClubBar.vue';
import SiteBanner from '@/components/SiteBanner.vue';
import { LANGS, language, setLanguage } from '@/i18n';
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
/** The admin and print pages are Czech only, so they offer no choice. */
const czechOnly = computed(() => /^(admin|print)/.test(String(route.name ?? '')));
</script>

<style>
.layout-wrapper {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.layout-header {
  background-color: #272727;
  color: white;
}

.layout-header .image {
  padding: 15px;
  /* Dark enough that the title reads over the red suits. */
  background: linear-gradient(rgba(0, 0, 0, 0.66), rgba(0, 0, 0, 0.66)), url('@/assets/suits.webp');
  background-size: cover;
}

.layout-header h1 {
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8), 0 0 18px rgba(0, 0, 0, 0.6);
}

.content {
  padding: 30px 15px;
  background-color: white;
  padding-bottom: 80px;
  flex-grow: 3;
  max-width: 100%;
}


.container {
  max-width: 1020px;
  margin: 0 auto;
}

@media (max-width: 640px) {
  .content {
    padding: 20px 4px 60px;
  }
}


nav {
  padding: 10px 30px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: center;
}

.body {
  min-height: 100vh;
}

footer {
  background-color: #272727;
  color: lightgray;
  display: flex;
  flex-direction: column;
}

.footer-wrapper {
  padding: 10px 15px;
  /* Same shade as the header, so text on it reads as well. */
  background: linear-gradient(rgba(0, 0, 0, 0.72), rgba(0, 0, 0, 0.72)), url('@/assets/suits.webp');
  background-size: cover;
  display: flex;
  justify-content: center;
}

.footer-inner {
  width: 100%;
  max-width: 1140px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.webmaster {
  width: 100%;
  max-width: 1140px;
  margin: 4px 0 6px;
  padding: 0 4px;
  color: rgba(255, 255, 255, 0.6);
  font-family: 'Lexend', Verdana, sans-serif;
  font-size: 0.8125rem;
  text-align: left;
}

.webmaster a {
  color: rgba(255, 255, 255, 0.85);
  text-decoration: underline;
  text-decoration-color: rgba(255, 255, 255, 0.4);
  text-underline-offset: 3px;
}

.webmaster a:hover {
  color: white;
}

.webmaster {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 4px 16px;
}

.webmaster .lang strong {
  color: rgba(255, 255, 255, 0.85);
  font-weight: 500;
}

@media print {



  h3, h2 {
    margin: 0;
  }

  .layout-header {
    background-color: unset;
    color: var(--primary-color);
  }

  @page {
     margin: 25mm 25mm 25mm 25mm;  
  }

  .content {
    padding: 0;
  }

  .container {
    max-width: unset;
  }

  .layout-header .image {
    padding: 0;
    background: unset;
  }

  .layout-header h1 {
    text-shadow: none;
  }

  .footer-wrapper {
    display: none;
  }

  nav {
    display: none;
  }
}
</style>
