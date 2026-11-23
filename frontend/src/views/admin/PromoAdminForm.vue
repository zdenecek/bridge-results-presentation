<template>
  <section>
    <div class="admin-head">
      <h2>Přihlášky do další soutěže</h2>
    </div>
    <p class="muted">
      Karta s tlačítkem k přihlášce. Na úvodní stránce je velká, na stránkách vybraného turnaje
      (typicky končícího cyklu) ještě větší. Sama zmizí v čas uzávěrky.
    </p>

    <p v-if="!form && !message" class="muted">Načítám…</p>
    <p v-if="!form && message" class="error">
      Nastavení se nepodařilo načíst: {{ message }}
      <button type="button" class="link-button" @click="load">Zkusit znovu</button>
    </p>

    <form v-if="form" class="admin-card admin-form" @submit.prevent="save">
      <label class="check"><input v-model="form.enabled" type="checkbox"> Zobrazovat na webu</label>
      <label class="wide">Nadpis <input v-model="form.title" :required="form.enabled" maxlength="255" placeholder="Skupinová A Zima 2026"></label>
      <label class="wide">Text <input v-model="form.text" maxlength="500" placeholder="Nový cyklus začíná v pondělí 30. 11. Přihlaste se do soboty 28. 11. do 18:00."></label>
      <label class="wide">Fakta, oddělená středníkem <input v-model="form.facts" maxlength="500" placeholder="Start 30. 11.; 5 kol"></label>
      <label class="wide">Nadpis anglicky <input v-model="form.titleEn" maxlength="255" :placeholder="suggestEnglish(form.title)"></label>
      <label class="wide">Text anglicky <input v-model="form.textEn" maxlength="500" placeholder="Bez něj se v angličtině ukáže český text"></label>
      <label class="wide">Fakta anglicky <input v-model="form.factsEn" maxlength="500" placeholder="Starts 30 Nov; 5 rounds"></label>
      <label class="grow">Odkaz na přihlášku <input v-model="form.url" type="url" :required="form.enabled" maxlength="500" placeholder="https://www.bkpraha.cz/prihlasky-ska/"></label>
      <label>Text tlačítka <input v-model="form.linkLabel" maxlength="100" placeholder="Přihlásit se" size="16"></label>
      <label>Anglicky <input v-model="form.linkLabelEn" maxlength="100" placeholder="Register" size="16"></label>
      <label>Uzávěrka <input v-model="form.closes" type="datetime-local"></label>
      <label class="grow">Největší na stránkách turnaje
        <select v-model="form.tournament">
          <option :value="null">jen na úvodní stránce</option>
          <option v-for="t in tournaments" :key="t.slug" :value="t.slug">{{ t.name }}</option>
        </select>
      </label>
      <button class="button primary" type="submit" :disabled="busy">Uložit</button>
      <p v-if="message" :class="isError ? 'error' : 'muted'" class="wide">{{ message }}</p>
    </form>

    <template v-if="form?.title">
      <h3>Náhled</h3>
      <RegistrationPromo :preview="form" />
      <RegistrationPromo v-if="form.tournament" :preview="form" hero />
    </template>
  </section>
</template>

<script setup lang="ts">
import PromoApi, { PromoSettings } from '@/api/PromoApi';
import TournamentApi from '@/api/TournamentApi';
import RegistrationPromo from '@/components/RegistrationPromo.vue';
import { suggestEnglish } from '@/i18n/names';
import { adminKey } from '@/utils/adminKey';
import { ref } from 'vue';

const form = ref<PromoSettings>();
const tournaments = ref<{ name: string; slug: string }[]>([]);
const busy = ref(false);
const message = ref('');
const isError = ref(false);

function load() {
  message.value = '';
  PromoApi.getSettings(adminKey.value).then((data) => form.value = data).catch((e) => fail(e));
}
load();
TournamentApi.getTournaments().then((list) => tournaments.value = [...list].reverse()).catch(() => undefined);

function fail(e: any) {
  isError.value = true;
  message.value = e?.response?.data?.message ?? e?.message ?? String(e);
}

async function save() {
  if (!form.value) return;
  busy.value = true;
  message.value = '';
  try {
    form.value = await PromoApi.save(adminKey.value, form.value);
    isError.value = false;
    message.value = form.value.enabled ? 'Uloženo, přihlášky jsou na webu (do pěti minut).' : 'Uloženo, přihlášky jsou skryté.';
  } catch (e) {
    fail(e);
  } finally {
    busy.value = false;
  }
}
</script>

<style scoped>
section {
  margin-top: 40px;
}

.admin-form label.check {
  flex-direction: row;
  align-items: center;
  gap: 8px;
  width: 100%;
  font-weight: 600;
}

.admin-form .grow {
  flex: 1;
  min-width: 240px;
}

.admin-form p.wide {
  width: 100%;
  margin: 0;
}
</style>
