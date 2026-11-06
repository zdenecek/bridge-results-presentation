<template>
  <button type="button" class="settings-open" :aria-label="$t('settings.title')" :title="$t('settings.title')" @click="open">
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M12 15.2a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4Z" fill="none" stroke="currentColor" stroke-width="1.8" />
      <path d="M19.4 13.5a7.6 7.6 0 0 0 0-3l2-1.6-2-3.4-2.4 1a7.5 7.5 0 0 0-2.6-1.5L14 2.5h-4l-.4 2.5A7.5 7.5 0 0 0 7 6.5l-2.4-1-2 3.4 2 1.6a7.6 7.6 0 0 0 0 3l-2 1.6 2 3.4 2.4-1a7.5 7.5 0 0 0 2.6 1.5l.4 2.5h4l.4-2.5a7.5 7.5 0 0 0 2.6-1.5l2.4 1 2-3.4Z"
            fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
    </svg>
    <span v-if="following" class="dot" aria-hidden="true"></span>
  </button>

  <dialog ref="dialog" class="settings" :aria-label="$t('settings.title')" @click="closeOnBackdrop">
    <form method="dialog" class="settings-inner">
      <header>
        <h2>{{ $t('settings.title') }}</h2>
        <button class="close" :aria-label="$t('settings.close')">×</button>
      </header>

      <section>
        <h3>{{ $t('settings.following') }}</h3>
        <label v-if="pairs.length" class="field">
          <span>{{ $t('settings.pairHere') }}</span>
          <select :value="followedPair(tournamentKey) ?? ''" @change="onPair">
            <option value="">{{ $t('settings.nobody') }}</option>
            <option v-for="pair in pairs" :key="pair.id" :value="pair.id">{{ pair.id }}. {{ pair.title }}</option>
          </select>
        </label>
        <label class="field">
          <span>{{ $t('settings.playerEverywhere') }}</span>
          <input v-model.trim="settings.followPlayer" inputmode="numeric" list="settings-players"
                 :placeholder="$t('settings.cbsNumber')" autocomplete="off">
          <datalist id="settings-players">
            <option v-for="player in players" :key="player.id" :value="player.id">{{ player.name }}</option>
          </datalist>
          <small v-if="followedPlayerName" class="hint">{{ followedPlayerName }}</small>
          <small v-else class="hint">{{ $t('settings.playerHint') }}</small>
        </label>
        <button type="button" class="link-button" :disabled="!following" @click="resetFollowing">{{ $t('settings.reset') }}</button>
      </section>

      <section>
        <h3>{{ $t('settings.display') }}</h3>
        <label class="check">
          <input v-model="settings.showHcp" type="checkbox">
          <span>{{ $t('settings.showHcp') }}</span>
        </label>
        <div class="field">
          <span>{{ $t('settings.order') }}</span>
          <div class="segmented" role="radiogroup">
            <label v-for="order in (['score', 'table'] as const)" :key="order" :class="{ on: settings.resultsOrder === order }">
              <input v-model="settings.resultsOrder" type="radio" name="results-order" :value="order">
              {{ $t(`settings.order_${order}`) }}
            </label>
          </div>
        </div>
        <div v-if="!czechOnly" class="field">
          <span>{{ $t('lang.label') }}</span>
          <div class="segmented" role="radiogroup">
            <label v-for="lang in LANGS" :key="lang" :class="{ on: language === lang }" :lang="lang">
              <input type="radio" name="language" :checked="language === lang" @change="setLanguage(lang)">
              {{ $t(`lang.${lang}Name`) }}
            </label>
          </div>
        </div>
      </section>

      <section class="report">
        <h3>{{ $t('settings.problem') }}</h3>
        <p v-if="tournament?.td">
          {{ $t('settings.tdHelp') }} <strong>{{ tournament.td.name }}</strong>
          <template v-if="tournament.td.email">, <a :href="'mailto:' + tournament.td.email">{{ tournament.td.email }}</a></template>
          <template v-if="tournament.td.phone">, <a :href="'tel:' + tournament.td.phone">{{ tournament.td.phone }}</a></template>
        </p>
        <p>
          {{ $t('settings.bug') }} <a :href="bugMail">zdnek.tomis@gmail.com</a>
        </p>
      </section>
    </form>
  </dialog>
</template>

<script setup lang="ts">
import { Ref, computed, inject, ref } from 'vue';
import { useRoute } from 'vue-router';
import { Tournament } from '@/model/Tournament';
import { LANGS, language, setLanguage } from '@/i18n';
import { followedPair, resetFollowing, setFollowedPair, settings } from '@/settings';

const route = useRoute();
const tournament = inject<Ref<Tournament | undefined>>('tournament', ref(undefined));
const tournamentKey = computed(() => String(route.params.tournament ?? ''));
const czechOnly = computed(() => /^(admin|print)/.test(String(route.name ?? '')));

const pairs = computed(() => route.params.tournament
  ? [...(tournament.value?.players.values() ?? [])].filter((p) => !p.isBye).sort((a, b) => a.id - b.id)
  : []);
const players = computed(() => pairs.value.flatMap((p) => p.players).filter((p) => p.id));
const followedPlayerName = computed(() => players.value.find((p) => p.id === settings.followPlayer)?.name);
const following = computed(() => !!settings.followPlayer || Object.keys(settings.followPairs).length > 0);

const bugMail = computed(() => `mailto:zdnek.tomis@gmail.com?subject=${encodeURIComponent('vysledky.bkpraha.cz')}&body=${encodeURIComponent(window.location.href + '\n\n')}`);

/** The bundled DOM types predate showModal and close. */
const dialog = ref<HTMLDialogElement & { showModal(): void; close(): void }>();
function open() {
  dialog.value?.showModal();
}

function closeOnBackdrop(event: MouseEvent) {
  if (event.target === dialog.value) dialog.value?.close();
}

function onPair(event: Event) {
  const value = (event.target as HTMLSelectElement).value;
  setFollowedPair(tournamentKey.value, value ? Number(value) : undefined);
}
</script>

<style scoped>
.settings-open {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  padding: 0;
  border: 1px solid #cfd8cf;
  border-radius: 50%;
  background: white;
  color: #1a881a;
  cursor: pointer;
}

.settings-open:hover {
  background: #eef5ee;
  border-color: #1a881a;
}

.settings-open svg {
  width: 22px;
  height: 22px;
}

.dot {
  position: absolute;
  top: 1px;
  right: 1px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #e0a800;
  border: 2px solid white;
}

.settings {
  width: min(440px, calc(100vw - 24px));
  max-height: calc(100dvh - 24px);
  padding: 0;
  border: none;
  border-radius: 16px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
  color: #222;
  font-family: 'Lexend', Verdana, sans-serif;
  text-align: left;
}

.settings::backdrop {
  background: rgba(0, 0, 0, 0.45);
}

/* Not the site's centred forms. */
.settings-inner {
  display: block;
  padding: 16px 20px 20px;
}

header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

h2 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #222;
}

.close {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 50%;
  background: transparent;
  font-size: 1.6rem;
  line-height: 1;
  cursor: pointer;
  color: #5f6661;
}

.close:hover {
  background: #f0f2f0;
}

section {
  padding: 14px 0;
  border-top: 1px solid #e3e6e3;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

section:first-of-type {
  border-top: none;
}

h3 {
  margin: 0;
  font-size: 0.8125rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #5f6661;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 5px;
  font-size: 0.9375rem;
}

select,
input:not([type]),
input[inputmode] {
  width: 100%;
  padding: 9px 10px;
  border: 1px solid #cfd8cf;
  border-radius: 8px;
  font: inherit;
  background: white;
}

select:focus,
input:focus {
  outline: 2px solid #1a881a;
  outline-offset: 0;
}

.hint {
  color: #5f6661;
  font-size: 0.8125rem;
}

.check {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.9375rem;
}

.check input {
  width: 18px;
  height: 18px;
  accent-color: #1a881a;
}

.segmented {
  display: flex;
  border: 1px solid #cfd8cf;
  border-radius: 999px;
  overflow: hidden;
}

.segmented label {
  flex: 1;
  padding: 8px 10px;
  text-align: center;
  cursor: pointer;
  font-size: 0.875rem;
}

.segmented label + label {
  border-left: 1px solid #cfd8cf;
}

.segmented label.on {
  background: #1a881a;
  color: white;
  font-weight: 600;
}

.segmented input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.segmented label:has(input:focus-visible) {
  outline: 2px solid #0e5a1b;
  outline-offset: -3px;
}

.link-button {
  align-self: flex-start;
  padding: 0;
  border: none;
  background: none;
  color: #b3261e;
  font: inherit;
  font-size: 0.875rem;
  text-decoration: underline;
  text-underline-offset: 3px;
  cursor: pointer;
}

.link-button:disabled {
  color: #9aa09b;
  text-decoration: none;
  cursor: default;
}

.report p {
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.5;
}

.report a {
  color: #1a881a;
}
</style>
