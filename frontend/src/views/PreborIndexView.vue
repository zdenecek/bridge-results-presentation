<template>
  <main-layout>
    <template #header>
      {{ $t('prebor.indexTitle') }}
    </template>
    <template #default>
      <p v-if="state === 'loading'" class="note">{{ $t('common.loading') }}</p>
      <p v-else-if="state === 'error'">{{ $t('prebor.indexError') }}</p>
      <div v-else class="flex flex-column gap-small">
        <section v-for="season in seasons" :key="season">
          <h3>{{ season }}</h3>
          <ul>
            <li v-for="c in competitions.filter((c) => c.season === season)" :key="c.slug">
              <router-link :to="{ name: 'prebor-ranking', params: { slug: c.slug } }">{{ localName(language, c.title, c.title_en) }}</router-link>
              <span class="note">{{ $t('prebor.indexPlayed', { played: c.played, rounds: c.rounds }) }}</span>
            </li>
          </ul>
        </section>
      </div>
    </template>
  </main-layout>
</template>

<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { language } from '@/i18n';
import { localName } from '@/i18n/names';
import PreborApi, { PreborCompetitionSummary } from '@/api/PreborApi';
import { computed, ref } from 'vue';

const competitions = ref<PreborCompetitionSummary[]>([]);
const state = ref<'loading' | 'loaded' | 'error'>('loading');
const seasons = computed(() => [...new Set(competitions.value.map((c) => c.season))]);

PreborApi.getCompetitions().then((data) => {
  competitions.value = data;
  state.value = 'loaded';
}).catch((e) => {
  console.error(e);
  state.value = 'error';
});
</script>

<style scoped>
.note {
  color: #666;
}

ul {
  list-style: none;
  padding: 0;
}
</style>
